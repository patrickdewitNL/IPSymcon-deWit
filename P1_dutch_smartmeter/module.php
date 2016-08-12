<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public $buffer = '';
		
		
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
			$this->RegisterPropertyInteger("DaysToKeep", "");
			$this->RegisterPropertyString("Password", "");
			
			
			// Make a variable that works as buffer for received data. Might change in future for Class build-in buffers
			$this->RegisterVariableString('Buffer', 'Buffer', "", -1);
			IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
			
			
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$keep = true;
			$this->MaintainVariable("consumptionT1", "Afname laagtarief", 2, "Electricity", 10, $keep);
			$this->MaintainVariable("consumptionT2", "Afname hoogtarief", 2, "Electricity", 20, $keep);
			$this->MaintainVariable("currentConsumption", "Huidig verbruik", 2, "Watt.3680", 30, $keep);
			$this->MaintainVariable("productionT1", "Productie laagtarief", 2, "Electricity", 10, $keep);
			$this->MaintainVariable("productionT2", "Productie hoogtarief", 2, "Electricity", 20, $keep);
			$this->MaintainVariable("currentProduction", "Huidig opwek", 2, "Watt.3680", 30, $keep);
			$this->MaintainVariable("consumptionGas", "Gas afname", 2, "Gas", 30, $keep);


			
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
		
		  //------------------------------------------------------------------------------
		/**
		 * Get status variable Buffer
		 * contains incoming data from IO, act as regVar
		 * @return String
		 */
		private function GetBuffer()
		{
			$id = $this->GetIDForIdent('Buffer');
			$val = GetValueString($id);
			return $val;
		}
		//------------------------------------------------------------------------------
		/**
		 * Set status variable Buffer
		 * @param String $val
		 */
		private function SetBuffer($val)
		{
			$id = $this->GetIDForIdent('Buffer');
			SetValueString($id, $val);
		}

	}
?>