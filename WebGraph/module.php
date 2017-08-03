<?

	class WebGraph extends IPSModule {
		
		public function Create() {
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("Username", "");
			$this->RegisterPropertyString("Password", "");
		}
	
		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterHook("/hook/webgraph");
		}
		
		private function RegisterHook($WebHook) {
			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
			if(sizeof($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
				$found = false;
				foreach($hooks as $index => $hook) {
					if($hook['Hook'] == $WebHook) {
						if($hook['TargetID'] == $this->InstanceID)
							return;
						$hooks[$index]['TargetID'] = $this->InstanceID;
						$found = true;
					}
				}
				if(!$found) {
					$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
				}
				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}
	
		/**
		* This function will be called by the hook control. Visibility should be protected!
		*/
		protected function ProcessHookData() {

			if($_IPS['SENDER'] == "Execute") {
				echo "This script cannot be used this way.";
				return;
			}
			
			if((IPS_GetProperty($this->InstanceID, "Username") != "") || (IPS_GetProperty($this->InstanceID, "Password") != "")) {
				if(!isset($_SERVER['PHP_AUTH_USER']))
					$_SERVER['PHP_AUTH_USER'] = "";
				if(!isset($_SERVER['PHP_AUTH_PW']))
					$_SERVER['PHP_AUTH_PW'] = "";
					
				if(($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($this->InstanceID, "Username")) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($this->InstanceID, "Password"))) {
					header('WWW-Authenticate: Basic Realm="Geofency WebHook"');
					header('HTTP/1.0 401 Unauthorized');
					echo "Authorization required";
					return;
				}
			}
			
			if(!isset($_GET['id'])) {
				die("Missing parameter: id");
			}

			$id = intval($_GET['id']);
			if(!IPS_VariableExists($id)) {
				echo "Invalid VariableID";
				return;
			}

			$startTime = time();
            if(isset($_GET['startTime']) && $_GET['startTime'] != "") {
                $startTime = strtotime($_GET['startTime']);
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
            if(isset($_GET['timeSpan'])) {
                $timeSpan = intval($_GET['timeSpan']);
            }

            $isHighDensity = false;
            if(isset($_GET['isHighDensity'])) {
                $isHighDensity = intval($_GET['isHighDensity']);
            }

			$isExtrema = false;
            if(isset($_GET['isExtrema'])) {
                $isExtrema = intval($_GET['isExtrema']);
            }

            $isDynamic = false;
            if(isset($_GET['isDynamic'])) {
                $isDynamic = intval($_GET['isDynamic']);
            }

			$width = 800;
            if(isset($_GET['width']) && intval($_GET['width']) > 0) {
                $width = intval($_GET['width']);
            }

			$height = 600;
            if(isset($_GET['height']) && intval($_GET['height']) > 0) {
                $height = intval($_GET['height']);
            }

            //Fixup startTime
			switch($timeSpan) {
				case 0: //Hour
					$startTime = mktime(date("H", $startTime), 0, 0, date("m", $startTime), date("d", $startTime), date("Y", $startTime));
					break;
				case 1: //Day
                    $startTime = mktime(0, 0, 0, date("m", $startTime), date("d", $startTime), date("Y", $startTime));
                    break;
				case 2: //Week
                    $startTime = mktime(0, 0, 0, date("m", $startTime), date("d", $startTime) - date("N", $startTime) + 1, date("Y", $startTime));
                    break;
				case 3: //Month
                    $startTime = mktime(0, 0, 0, date("m", $startTime), 1, date("Y", $startTime));
                    break;
				case 4: //Year
                    $startTime = mktime(0, 0, 0, 1, 1, date("Y", $startTime));
                    break;
				case 5: //Decade
                    $startTime = mktime(0, 0, 0, 1, 1, floor(date("Y", $startTime) / 10) * 10);
                    break;
				default:
					echo "Invalid timespan";
					return;
			}

			$css = file_get_contents(__DIR__ . "/style.css");
			$acID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
			$chart = AC_RenderChart($acID, $id, $startTime, $timeSpan, $isHighDensity, $isExtrema, $isDynamic, $width, $height);

			//Bail out on error
			if($chart === false) {
				return;
			}

			$title = $this->Translate("Start time") . ": " . date("d.m.Y H:i", $startTime);

			echo <<<EOT
<html>
<head><style>body { background: black; color: white; font-family: Verdana } $css</style></head>
<body>
<div class="ipsChart">
$title
<br/>
$chart
</div>
</body>
EOT;

		}
		
	}

?>
