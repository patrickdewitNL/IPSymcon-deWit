<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$guid = '"{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"';
			
			$this->RequireParent($guid);
			
			$pid = $this->GetParent();
			if ($pid) {
				$name = IPS_GetName($pid);
				if ($name == "Serial Port") IPS_SetName($pid, "Serial Port for P1 smartmeter");
			}
			
			COMPort_SetBaudRate($pid, 115200);
	
			
			
			$this->RegisterPropertyString("Username", "");
			$this->RegisterPropertyString("Password", "");
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
			IPS_LogMessage("P1 Smart meter", utf8_decode($data->Buffer));
		
		}
	}
?>