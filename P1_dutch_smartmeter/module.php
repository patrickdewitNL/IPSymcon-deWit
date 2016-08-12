<?
	
	class P1SmartMeter extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RequireParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}");
			
			
			$this->RegisterPropertyString("Username", "");
			$this->RegisterPropertyString("Password", "");
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->MaintainVariable("meterT1", "Afname dal", 2, "Electricity", 10, $keep);
			$this->MaintainVariable("meterT2", "Afname hoogtarief", 2, "Electricity", 20, $keep);
			$this->MaintainVariable("currentConsumption", "Huidig verbruik", 2, "Watt.3680", 30, $keep);

			
		}
		
		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			IPS_LogMessage("P1 Smart meter", utf8_decode($data->Buffer));
		
		}
	}
?>