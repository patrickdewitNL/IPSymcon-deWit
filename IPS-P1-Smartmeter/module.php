<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			// Requires a Serialport I/O as parent.
			$this->RequireParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
			$instance = IPS_GetInstance($this->InstanceID);
			$pid = $instance['ConnectionID'];
			
			if ($pid) {
				$name = IPS_GetName($pid);
				if ($name == "Serial Port") IPS_SetName($pid, "Serial Port for P1 smartmeter");
			}
			
			// Seems to work for most P1 meters, so set this as standard.
			COMPort_SetBaudRate($pid, 115200);
			
			// Set variables used for settings.
			$this->RegisterPropertyInteger("DaysToKeep", "10");
			
			// Set variables for smart meter data
			// Get ObjectID for first archive
			$archives = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
 
			
						
			$this->RegisterVariableFloat("consumptionT1", "Electricity consumption low", 2, "Electricity", 10);
			$this->RegisterVariableFloat("consumptionT2", "Electricity consumption high", 2, "Electricity", 20);
			$this->RegisterVariableFloat("currentConsumption", "Current usage", 2, "Watt.3680", 30);
			AC_SetLoggingStatus($this->GetIDForIdent('currentConsumption'), $archives[0], true)
			
			$this->RegisterVariableFloat("productionT1", "Electricity production low", 2, "Electricity", 10);
			$this->RegisterVariableFloat("productionT2", "Electricity production high", 2, "Electricity", 20);
			$this->RegisterVariableFloat("currentProduction", "Current production", 2, "Watt.3680", 30);
			AC_SetLoggingStatus($this->GetIDForIdent('currentProduction'), $archives[0], true)
			
			$this->RegisterVariableFloat("consumptionGas", "Gas consumption", 2, "Gas", 40);
			AC_SetLoggingStatus($this->GetIDForIdent('consumptionGas'), $archives[0], true)
			
			// Set timer for automatic data removal for historic data
			$this->RegisterTimer("DataRemoval", $this->ReadPropertyInteger("DaystoKeep"), 'P1_PurgeOldData');
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->SetTimerInterval("DataRemoval", $this->ReadPropertyInteger("DaystoKeep")*24*60*60*1000);
		}
		
		public function PurgeOldData()
		{
			IPS_LogMessage("P1 Smart meter", "Purge old data called");
			
			
		}
		
		
		
		private function ParseP1Telegram($Telegram)
		{
				if ($Telegram != '')
				{
					// Get and save current usage
					preg_match('@(1-0:1\.7\.0) ?\((\d+)\.(\d+)@', $Telegram, $matches);
					SetValue($this->GetIDForIdent("currentConsumption"), $matches[2] * 1000 + $matches[3]);

					// Get and save current production
					preg_match('@(1-0:2\.7\.0) ?\((\d+)\.(\d+)@', $Telegram, $matches);
					SetValue($this->GetIDForIdent("currentProduction"), $matches[2] * 1000 + $matches[3]);
					
					// Get and save gas usage
					preg_match('@(0-1:24\.2\.1) ?(\(.+\()(\d+)\.(\d+)@', $Telegram, $matches);
					SetValue($this->GetIDForIdent("consumptionGas"), $matches[3] + 0.001 * $matches[4]);

					
					// get afname-laag  
					//preg_match('@(1-0:1\.8\.1\() ?(\d+)\.(\d+)@', $telegram, $matches);
					//$afname_laag = $matches[2] + round(0.001 * $matches[3], 1);
					//SetValue(22543 /*[Verbruik\Afname: dal]*/ , $afname_laag);

					// get afname-hoog
					//preg_match('@(1-0:1\.8\.2\() ?(\d+)\.(\d+)@', $telegram, $matches);
					//$afname_hoog = $matches[2] + round(0.001 * $matches[3], 1);
					//SetValue(11263 /*[Verbruik\Afname: piek]*/ , $afname_hoog);
				}	
			
		}
		

		
		
		public function ReceiveData($JSONString)
		{
			
			$data = json_decode($JSONString);
            
			//data from buffer variable
            $buffer = $this->GetBuffer();
			
			// continue to add
			$buffer .= utf8_decode($data->Buffer);

			// When a ! is found we have a new complete telegram
			if (strpos($buffer, '!'))
			{
					IPS_LogMessage("P1 Smart meter", "Received new P1 telegram");
					$this->ParseP1Telegram($buffer);
					$buffer = '';
			}
					
			// Set new data in buffer
			$this->SetBuffer($buffer);
			
		
		}


	}
?>