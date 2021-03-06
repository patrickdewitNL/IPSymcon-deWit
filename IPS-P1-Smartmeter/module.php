<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			// Requires a Serialport I/O as parent.
			$this->ConnectParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
			$instance = IPS_GetInstance($this->InstanceID);
			$pid = $instance['ConnectionID'];
			
	
			// Set variables used for settings.
			$this->RegisterPropertyInteger("DaysToKeep", "10");
			$this->RegisterPropertyBoolean("SaveData", true);
					
			$this->RegisterVariableFloat("consumptionT1", "Electricity consumption low", "Electricity", 10);
			$this->RegisterVariableFloat("consumptionT2", "Electricity consumption high", "Electricity", 20);
			$this->RegisterVariableFloat("currentConsumption", "Current usage", "Watt.3680", 30);
			

			$this->RegisterVariableFloat("productionT1", "Electricity production low", "Electricity", 10);
			$this->RegisterVariableFloat("productionT2", "Electricity production high", "Electricity", 20);
			$this->RegisterVariableFloat("currentProduction", "Current production", "Watt.3680", 30);

			$this->RegisterVariableFloat("consumptionGas", "Gas consumption", "Gas", 40);

			
			// Set timer for automatic data removal for historic data
			$this->RegisterTimer("DataRemoval", 0, 'P1_PurgeOldData');
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->SetTimerInterval("DataRemoval", $this->ReadPropertyInteger("DaysToKeep")*24*60*60*1000);
			
			if ($this->ReadPropertyBoolean("SaveData") == true)
			{
				// Set variables for smart meter data
				// Get ObjectID for first archive
				$archives = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}");
 
			
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('consumptionT1'), true);
				AC_SetAggregationType ($archives[0], $this->GetIDForIdent('consumptionT1'), 1);

				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('consumptionT2'), true);
				AC_SetAggregationType ($archives[0], $this->GetIDForIdent('consumptionT2'), 1);

				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('currentConsumption'), true);
				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('currentProduction'), true);

				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('productionT1'), true);
				AC_SetAggregationType ($archives[0], $this->GetIDForIdent('productionT1'), 1);

				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('productionT2'), true);
				AC_SetAggregationType ($archives[0], $this->GetIDForIdent('productionT2'), 1);

				AC_SetLoggingStatus($archives[0], $this->GetIDForIdent('consumptionGas'), true);

			}
			
			
		

		}
		


		public function PurgeOldData()
		{
			IPS_LogMessage("P1 Smart meter", "Purge old data called");

			// number of days to keep all recorded data
			$daysToKeep = $this->ReadPropertyInteger("DaystoKeep");

			// compute delete-before date
			$deleteDate = time() - ($daysToKeep * 24 * 60 * 60);
			$startDate =  mktime(0, 0, 0, 1, 1, date('Y'));
			
			$vars = IPS_GetChildrenIDs($this->InstanceID);

			IPS_LogMessage($_IPS['SELF'], $vars);


			//foreach ($variables as $var)
			//{

			//	$result = (AC_DeleteVariableData(33402, $var, $startDate, $deleteDate)) ? 'pass' : 'failure';
			//	IPS_LogMessage($_IPS['SELF'], "Result for deleting variable ".$var." is ".$result);

			//	$result = (AC_ReAggregateVariable(33402, $var)) ? 'pass' : 'failure';
			//	IPS_LogMessage($_IPS['SELF'], "Result for reaggregating variable ".$var." is ".$result);

			//}
			//unset($var);
		}
		
		
		
		private function ParseP1Telegram($telegram)
		{
				if ($telegram != '')
				{
					IPS_LogMessage("P1 Smart meter", "Parsing new telegram");
					// Get and save current usage
					preg_match('@(1-0:1\.7\.0) ?\((\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("currentConsumption"), $matches[2] * 1000 + $matches[3]);

					// Get and save current production
					preg_match('@(1-0:2\.7\.0) ?\((\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("currentProduction"), $matches[2] * 1000 + $matches[3]);
					
					// Get and save gas usage
					preg_match('@(0-1:24\.2\.1) ?(\(.+\()(\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("consumptionGas"), $matches[3] + 0.001 * $matches[4]);

					// get afname-laag  
					preg_match('@(1-0:1\.8\.1\() ?(\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("consumptionT1") , $matches[2] + round(0.001 * $matches[3], 1));

					// get afname-hoog
					preg_match('@(1-0:1\.8\.2\() ?(\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("consumptionT2") , $matches[2] + round(0.001 * $matches[3], 1));
					
					// get teruggave-hoog 
					preg_match('@(1-0:2\.8\.2\() ?(\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("productionT1") , $matches[2] + round(0.001 * $matches[3], 1));
					
					// get teruggave-laag
					preg_match('@(1-0:2\.8\.1\() ?(\d+)\.(\d+)@', $telegram, $matches);
					SetValue($this->GetIDForIdent("productionT2") , $matches[2] + round(0.001 * $matches[3], 1));
				}	
			
		}
		

		
		
		public function ReceiveData($JSONString)
		{
			
			$data = json_decode($JSONString);
			

			$telegram = '';
			
			//data from buffer variable
			$buffer= $this->GetBuffer("P1");
			
			
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
			$this->SetBuffer("P1",$buffer);
			
		
		}


	}
?>