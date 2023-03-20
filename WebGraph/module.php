<?php

declare(strict_types=1);

    class WebGraph extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('Username', '');
            $this->RegisterPropertyString('Password', '');

            $this->RegisterPropertyString('AccessList', '[]');

            // Inspired by module SymconTest/HookServe
            // We need to call the RegisterHook function on Kernel READY
            $this->RegisterMessage(0, IPS_KERNELMESSAGE);
        }

        // Inspired by module SymconTest/HookServe
        public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
        {
            parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

            if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
                $this->RegisterHook('/hook/webgraph');
            }
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            // Inspired by module SymconTest/HookServe
            // Only call this in READY state. On startup the WebHook instance might not be available yet
            if (IPS_GetKernelRunlevel() == KR_READY) {
                $this->RegisterHook('/hook/webgraph');
            }

            // Update reference search
            $refs = $this->GetReferenceList();
            foreach ($refs as $ref) {
                $this->UnregisterReference($ref);
            }

            foreach (json_decode($this->ReadPropertyString('AccessList')) as $item) {
                $this->RegisterReference($item->ObjectID);
            }
        }

        private function TranslateChart($chart)
        {
            $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            $merged = array_merge($weekdays, $months);
            foreach ($merged as $str) {
                $chart = str_replace($str, $this->Translate($str), $chart);
            }

            return $chart;
        }

        private function IsAllowedObject($id)
        {
            $allowed = json_decode($this->ReadPropertyString('AccessList'));

            foreach ($allowed as $item) {
                if ($item->ObjectID == $id) {
                    return true;
                }
            }

            return false;
        }

        private function BuildCSSForMultiChart($mediaID)
        {
            $content = json_decode(base64_decode(IPS_GetMediaContent($mediaID)));

            $css = '/* Additional CSS for multi chart colorizing */' . PHP_EOL;
            $i = 1;
            foreach ($content->datasets as $dataset) {
                if ($dataset->fillColor == 'clear') {
                    $dataset->fillColor = 'transparent';
                }
                $css .= 'div.ipsChart > svg > g.graphs > g.background > g:nth-of-type(' . $i . ') path {' . PHP_EOL . '    fill: ' . $dataset->fillColor . ';' . PHP_EOL . '    opacity: 0.5; }' . PHP_EOL;
                $css .= 'div.ipsChart > svg > g.graphs > g.outline > g:nth-of-type(' . $i . ') path {' . PHP_EOL . '    stroke: ' . $dataset->strokeColor . '; }' . PHP_EOL;
                $i++;
            }

            return $css;
        }

        private function BuildLegendForMultiChart($mediaID)
        {
            $content = json_decode(base64_decode(IPS_GetMediaContent($mediaID)));

            $legend = '<div style="float: clear"></div><div style="float: left; margin-right: 10px">Name: </div>';
            foreach ($content->datasets as $dataset) {
                $legend .= '<div style="width: 16px; height: 16px; background: ' . $dataset->fillColor . '; border: 1px solid ' . $dataset->strokeColor . '; float: left; margin-right: 5px;"></div><div style="float: left; margin-right: 10px">' . IPS_GetName($dataset->variableID) . '</div>' . PHP_EOL;
            }
            return $legend;
        }

        private function RegisterHook($WebHook)
        {
            $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
            if (count($ids) > 0) {
                $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
                $found = false;
                foreach ($hooks as $index => $hook) {
                    if ($hook['Hook'] == $WebHook) {
                        if ($hook['TargetID'] == $this->InstanceID) {
                            return;
                        }
                        $hooks[$index]['TargetID'] = $this->InstanceID;
                        $found = true;
                    }
                }
                if (!$found) {
                    $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
                }
                IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
                IPS_ApplyChanges($ids[0]);
            }
        }

        public function GetConfigurationForm()
        {
            $formdata = json_decode(file_get_contents(__DIR__ . '/form.json'));

            $allowed = json_decode($this->ReadPropertyString('AccessList'));

            $options = [];
            $values = [];
            foreach ($allowed as $item) {
                if (IPS_ObjectExists($item->ObjectID)) {
                    $options[] = ['label' => IPS_GetName($item->ObjectID), 'value' => $item->ObjectID];
                }
                else {
                    $options[] = ['label' => sprintf($this->Translate('Invalid Object #%d'), $item->ObjectID), 'value' => $item->ObjectID];
                }
            }
            $formdata->actions[0]->options = $options;

            return json_encode($formdata);
        }

        /**
         * This function will be called by the hook control. Visibility should be protected!
         */
        protected function ProcessHookData()
        {
            if ($_IPS['SENDER'] == 'Execute') {
                echo 'This script cannot be used this way.';
                return;
            }

            if ((IPS_GetProperty($this->InstanceID, 'Username') != '') || (IPS_GetProperty($this->InstanceID, 'Password') != '')) {
                if (!isset($_SERVER['PHP_AUTH_USER'])) {
                    $_SERVER['PHP_AUTH_USER'] = '';
                }
                if (!isset($_SERVER['PHP_AUTH_PW'])) {
                    $_SERVER['PHP_AUTH_PW'] = '';
                }

                if (($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($this->InstanceID, 'Username')) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($this->InstanceID, 'Password'))) {
                    header('WWW-Authenticate: Basic Realm="Geofency WebHook"');
                    header('HTTP/1.0 401 Unauthorized');
                    echo 'Authorization required';
                    return;
                }
            }

            if (!isset($_GET['id'])) {
                die('Missing parameter: id');
            }

            $id = intval($_GET['id']);

            if (!$this->IsAllowedObject($id)) {
                echo 'This id is not allowed';
                return;
            }

            if (!IPS_VariableExists($id) && !IPS_MediaExists($id)) {
                echo 'Invalid VariableID/MediaID';
                return;
            }

            $startTime = time();
            if (isset($_GET['startTime']) && $_GET['startTime'] != '') {
                $startTime = intval($_GET['startTime']);
            }

            /*
             * 0 = Hour
             * 1 = Day
             * 2 = Week
             * 3 = Month
             * 4 = Year
             * 5 = Decade
             *
             */
            $timeSpan = 4;
            if (isset($_GET['timeSpan'])) {
                $timeSpan = intval($_GET['timeSpan']);
            }

            if (isset($_GET['isRawDensity']) && intval($_GET['isRawDensity'])) {
                $density = 2;
            } elseif (isset($_GET['isHighDensity']) && intval($_GET['isHighDensity'])) {
                $density = 1;
            } else {
                $density = 0;
            }

            $isExtrema = false;
            if (isset($_GET['isExtrema'])) {
                $isExtrema = intval($_GET['isExtrema']);
            }

            $isDynamic = false;
            if (isset($_GET['isDynamic'])) {
                $isDynamic = intval($_GET['isDynamic']);
            }

            $isContinuous = false;
            if (isset($_GET['isContinuous'])) {
                $isContinuous = intval($_GET['isContinuous']);
            }

            $width = 800;
            if (isset($_GET['width']) && intval($_GET['width']) > 0) {
                $width = intval($_GET['width']);
            }

            $height = 600;
            if (isset($_GET['height']) && intval($_GET['height']) > 0) {
                $height = intval($_GET['height']);
            }

            $showTitle = true;
            if (isset($_GET['showTitle'])) {
                $showTitle = intval($_GET['showTitle']);
            }

            $showLegend = true;
            if (isset($_GET['showLegend'])) {
                $showLegend = intval($_GET['showLegend']);
            }

            //Calculate proper startTime
            if ($isContinuous) {
                switch ($timeSpan) {
                    case 0: //Hour
                        $startTime = mktime(intval(date('H', $startTime)) - 1, intval(floor(intval(date('i', $startTime)) / 5) * 5 + 5), 0, intval(date('m', $startTime)), intval(date('d', $startTime)), intval(date('Y', $startTime)));
                        break;
                    case 1: //Day
                        $startTime = mktime(intval(date('H', $startTime)) + 1, 0, 0, intval(date('m', $startTime)), intval(date('d', $startTime)) - 1, intval(date('Y', $startTime)));
                        break;
                    case 2: //Week
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)), intval(date('d', $startTime)) - 7 + 1, intval(date('Y', $startTime)));
                        break;
                    case 3: //Month
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)) - 1, intval(date('d', $startTime)) + 1, intval(date('Y', $startTime)));
                        break;
                    case 4: //Year
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)) + 1, 1, intval(date('Y', $startTime)) - 1);
                        break;
                    case 5: //Decade
                        $startTime = mktime(0, 0, 0, 1, 1, intval(date('Y', $startTime)) - 9);
                        break;
                    default:
                        echo 'Invalid timespan';

                        return;
                }
            } else {
                switch ($timeSpan) {
                    case 0: //Hour
                        $startTime = mktime(intval(date('H', $startTime)), 0, 0, intval(date('m', $startTime)), intval(date('d', $startTime)), intval(date('Y', $startTime)));
                        break;
                    case 1: //Day
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)), intval(date('d', $startTime)), intval(date('Y', $startTime)));
                        break;
                    case 2: //Week
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)), intval(date('d', $startTime)) - intval(date('N', $startTime)) + 1, intval(date('Y', $startTime)));
                        break;
                    case 3: //Month
                        $startTime = mktime(0, 0, 0, intval(date('m', $startTime)), 1, intval(date('Y', $startTime)));
                        break;
                    case 4: //Year
                        $startTime = mktime(0, 0, 0, 1, 1, intval(date('Y', $startTime)));
                        break;
                    case 5: //Decade
                        $startTime = mktime(0, 0, 0, 1, 1, floor(intval(date('Y', $startTime)) / 10) * 10);
                        break;
                    default:
                        echo 'Invalid timespan';

                        return;
                }
            }

            $css = file_get_contents(__DIR__ . '/style.css');

            //Add CSS for multi charts
            if (IPS_MediaExists($id)) {
                $css .= PHP_EOL . PHP_EOL;
                $css .= $this->BuildCSSForMultiChart($id);
            }

            $legend = '';
            if ($showLegend) {
                if (IPS_MediaExists($id)) {
                    $legend = $this->BuildLegendForMultiChart($id) . '<br/>';
                } else {
                    $legend = 'Name: ' . IPS_GetName($id) . '<br/>';
                }
            }

            $acID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $chart = AC_RenderChart($acID, $id, $startTime, $timeSpan, $density, $isExtrema, $isDynamic, $width, $height);

            //Translate strings
            $chart = $this->TranslateChart($chart);

            //Bail out on error
            if ($chart === false) {
                return;
            }

            $title = '';
            if ($showTitle) {
                $title = $this->Translate('Start time') . ': ' . date('d.m.Y H:i', $startTime) . '<br/>';
            }

            echo <<<EOT
<html>
<head><style>body { background: black; color: white; font-family: Verdana } $css</style></head>
<body>
<div class="ipsChart">
$title
$legend
$chart
</div>
</body>
EOT;
        }
    }
