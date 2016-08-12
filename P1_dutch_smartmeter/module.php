<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public $buffer = '';
		
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RequireParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
			
			
			$instance = IPS_GetInstance($this->InstanceID);
			$pid= $instance['ConnectionID'];
			
			
			if ($pid) {
				$name = IPS_GetName($pid);
				if ($name == "Serial Port") IPS_SetName($pid, "Serial Port for P1 smartmeter");
			}
			
			COMPort_SetBaudRate($pid, 115200);
			
			$this->RegisterPropertyString("Username", "");
			$this->RegisterPropertyString("Password", "");
			
		//	$this->RegisterVariableString('Buffer', 'Buffer', "", -1);
	//		IPS_SetHidden($this->GetIDForIdent('Buffer'), true);
			
			
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


			
		}
		
		public function ReceiveData($JSONString)
		{
			
			$data = json_decode($JSONString);
            
			//entry for data from parent
            //$buffer = $this->GetBuffer();
			
			$telegram = '';
			// continue to add
			$this->buffer .= utf8_decode($data->Buffer);

			// When a ! is found we have a new complete telegram
			if (strpos($this->buffer, '!'))
			{
					IPS_LogMessage("P1 Smart meter compleet telegram", $this->buffer);

					$this->buffer = '';
			}
					
			
			//$this->SetBuffer($buffer);
			
		
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