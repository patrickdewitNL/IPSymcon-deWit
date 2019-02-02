<?
/*
  MQTT Module IPSymcon PHP Splitter Module Class
  uses TFphpMQTT.php
  author Thomas Feldmann
  copyright Thomas Feldmann 2017
  version 0.1.0
  date 2017-03-18
 */
include_once(__DIR__ . "/module_helper.php");
include_once(__DIR__ . "/TFphpMQTT.php");

// Klassendefinition
class IPS_MQTT_Client extends T2FModule {
    //------------------------------------------------------------------------------
    //module const and vars
    //------------------------------------------------------------------------------
    /**
     * MQTT QOS constant "At Most once" (Fire and forget)
     * Used here for publishing, no need to take care
     * @see http://www.hivemq.com/blog/mqtt-essentials-part-6-mqtt-quality-of-service-levels
     */
    const MQTT_QOS_0_AT_MOST_ONCE=0;

    /**
     * MQTT QOS setting
     * set to QOS=0 because we are publisher only
     * @var int $qos
     */
    private $qos=self::MQTT_QOS_0_AT_MOST_ONCE;
    /**
     * MQTT Retain setting
     * @var boolean $retained
     */
    private $retained=false;

    private $mqtt;

    // Der Konstruktor des Moduls
    public function __construct($InstanceID)
    {
        // Diese Zeile nicht löschen
        $json = __DIR__ . "/module.json";
        parent::__construct($InstanceID, $json);

        $sClass = $this->GetBuffer("MQTT");
        IF ($sClass <> ""){
            $this->mqtt = unserialize($sClass);
        }else{
            $this->mqtt = NULL;
        }
        $this->conecting = false;


    }

    public function Destroy() {
        //Never delete this line!
        parent::Destroy();
        $this->SendDebug(__FUNCTION__, 'Destroy MQTT Disconnect',0);
        $this->MQTTDisconnect();
    }

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        // Selbsterstellter Code
        $this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");    //Client Socket Module
        $this->RegisterPropertyString('ClientID', 'IP-symcon');
        $this->RegisterPropertyString('User', '');
        $this->RegisterPropertyString('Password', '');
        //$this->RegisterPropertyBoolean('Active', false);

        //register status msg
        $this->RegisterMessage(0, self::IPS_KERNELMESSAGE );

    }

    //--------------------------------------------------------
    /**
     * overwrite internal IPS_ApplChanges($id) function
     */
    public function ApplyChanges()
    {
        $this->RegisterMessage(0, 10000);
        $this->RegisterMessage(0, 10001);
        $this->RegisterMessage(0,10403);
        $cID = $this->GetConnectionID();
        if($cID <> 0) {
            $this->RegisterMessage($cID, self::IM_CHANGESTATUS);
            $cID = $this->GetConnectionID();
            if (IPS_GetProperty($cID, "Host") != NULL and IPS_GetProperty($cID, "Port") != 0) {
                IPS_SetProperty($cID, "Open", TRUE); //I/O Instanz soll aktiviert sein.
                IPS_ApplyChanges($cID); //Neue Konfiguration übernehmen
            }
        }

        // Diese Zeile nicht loeschen
        parent::ApplyChanges();
/**
        if ($this->isActive()) {
            $this->SetStatus(self::ST_AKTIV);
            $this->debug(__FUNCTION__,"Modul Akteviert");  // Debug Fenster
            //$this->MQTTConnect();
        } else {
            $this->SetStatus(self::ST_INACTIV);
            $this->debug(__FUNCTION__,"Modul deaktiviert");  // Debug Fenster
            $this->MQTTDisconnect(1);
        }
**/

    }

    //------------------------------------------------------------------------------
    /**
     * Get Property category name to be created and used for Device Instnaces
     * @return string
     */
    private function GetUser()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'User');
    }

    //------------------------------------------------------------------------------
    /**
     * Get Property category name to be created and used for Device Instnaces
     * @return string
     */
    private function GetPassword()
    {
        return (String)IPS_GetProperty($this->InstanceID, 'Password');
    }

    //------------------------------------------------------------------------------
    /**
     * Get Property Port
     * @return string
     */
    private function GetClientID()
    {
        $random = rand(1, 100);
        $clientid=(String)IPS_GetProperty($this->InstanceID, 'ClientID');
        $clientid .= "_".$random;
        //$clientid.="@".gethostname();
        return $clientid;
    }

    /**
     * Get Property Port
     * @return string
     */
    private function GetConnectionID()
    {
        $data=IPS_GetInstance($this->InstanceID);
        return $data['ConnectionID'];
    }



    //------------------------------------------------------------------------------
    //---Events
    //------------------------------------------------------------------------------

    /**
     * Handle Message Events
     * will be called from IPS message loop for registered objects and events
     *
     * @param int $TimeStamp Timestamp of Event (looks not filled)
     * @param int $SenderID related object ID
     * @param int $Message related Message ID
     * @param array $Data Payload (content depends on Message ID)
     *
     *  @see https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/nachrichten/
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
//            $this->debug(__FUNCTION__,"entered");
        $id=$SenderID;
        $this->debug(__FUNCTION__, "TS: $TimeStamp SenderID ".$SenderID." with MessageID ".$Message." Data: ".print_r($Data, true));
        switch ($Message) {
            case self::VM_UPDATE:
                $this->Publish($id,$Data);
                break;
            case self::VM_DELETE:
                $this->UnSubscribe($id);
                break;
            case self::IM_CHANGESTATUS:
                switch ($Data[0]) {
                    case self::ST_AKTIV:
                        $this->debug(__CLASS__,__FUNCTION__."I/O Modul > Aktiviert");
                        // $this->MQTTDisconnect(2);
                        IPS_Sleep(500);
                        $this->debug(__CLASS__,__FUNCTION__."I/O Modul > Aktiviert 500 Millisekunden");
                        if (is_null($this->mqtt)) {
                            $this->MQTTConnect();
                        }

                        break;
                    case self::ST_INACTIV:
                        $this->debug(__CLASS__,__FUNCTION__."I/O Modul > Deaktiviert");
                        if($this->GetInstanceStatus() == self::ST_AKTIV ){
                            $this->MQTTDisconnect(4);
                        }
                        break;
                    case self::ST_ERROR_0:
                        $this->debug(__CLASS__,__FUNCTION__."I/O Modul > Fehler");
                        $this->MQTTDisconnect(4);
                        IPS_Sleep(500);
                        if ($this->HasActiveParent()) {
                            $this->MQTTConnect();
                        }
                        break;
                    default:
                        IPS_LogMessage(__CLASS__,__FUNCTION__."I/O Modul unbekantes Ereignis ".$Data[0]);
                        break;
                }
                break;
            case 10001:  // IPS_KERNELSTARTED
                IPS_LogMessage(__CLASS__,__FUNCTION__." IPS_KERNELSTARTED");
                $this->mqtt = NULL;
                //$this->MQTTConnect();
                break;
            case 10403: // Parent changed
                IPS_LogMessage(__CLASS__,__FUNCTION__." Parend changed");
                break;
            case self::IPS_KERNELMESSAGE:
                $kmsg=$Data[0];
                switch ($kmsg) {

                    case self::KR_READY:
                        IPS_LogMessage(__CLASS__,__FUNCTION__." KR_Ready ->reconect");
                        // $this->MQTTDisconnect(5);
                        break;
                    /*
                                           case self::KR_UNINIT:
                                               // not working :(
                                               $msgid=$this->GetBuffer("MsgID");
                                               IPS_SetProperty($this->InstanceID,'MsgID',(Integer)$msgid);
                                               IPS_ApplyChanges($this->InstanceID);
                                               IPS_LogMessage(__CLASS__,__FUNCTION__." KR_UNINIT ->disconnect()");
                                               break;  */

                    default:
                        IPS_LogMessage(__CLASS__,__FUNCTION__." Kernelmessage unhahndled, ID".$kmsg);
                        break;
                }
                break;
            default:
                IPS_LogMessage(__CLASS__,__FUNCTION__." Unknown Message $Message");
                break;
        }
//            $this->debug(__FUNCTION__,"leaved");

    }

    //------------------------------------------------------------------------------
    //Data Interfaces to parant
    //------------------------------------------------------------------------------

    /**
     * Data Interface from Parent(IO-RX)
     * @param string $JSONString
     */
    public function ReceiveData($JSONString)
    {

        if (!is_null($this->mqtt)){
            /**
            //status check triggered by data
        if ($this->isActive()) {
                $this->SetStatus(self::ST_AKTIV);
            } else {
                $this->SetStatus(self::ST_INACTIV);
                $this->debug(__FUNCTION__, 'Data arrived, but dropped because inactiv:' . $JSONString);
                return;
            }
**/
            // decode Data from Device Instanz
            if (strlen($JSONString) > 0) {
                $this->debug(__FUNCTION__, 'Data arrived:' . $JSONString);
                $this->debuglog($JSONString);
                // decode Data from IO Instanz
                $data = json_decode($JSONString);
                //entry for data from parent

                if (is_object($data)) { $data = get_object_vars($data);}
                if (isset($data['DataID'])) {
                    $target = $data['DataID'];
                    if ($target == $this->module_interfaces['IO-RX']) {
                        $buffer = utf8_decode($data['Buffer']);
                        $this->debug(__FUNCTION__, strToHex($buffer));
                        $this->mqtt->receive($buffer);
                        $sClass = serialize($this->mqtt);
                        $this->SetBuffer ("MQTT",$sClass);
                        // Ping Timer neu setzen
                        // $this->RegisterTimerNow('Ping', $this->mqtt->keepalive*1000,  'MQTT_TimerEvent('.$this->InstanceID.');');
                    }//target
                }//dataid
                else {
                    $this->debug(__FUNCTION__, 'No DataID supplied');
                }//dataid
            } else {
                $this->debug(__FUNCTION__, 'strlen(JSONString) == 0');
            }//else len json

        }else{
            $this->debug(__FUNCTION__, '$this->mqtt == null');
            if ($this->HasActiveParent()) {
                $this->MQTTConnect();
            }
        }
    }//func

    public function ForwardData($JSONString) {
        $this->debug(__FUNCTION__, 'Data Forward:' . $JSONString);
        $data = json_decode($JSONString);
        $Buffer = utf8_decode($data->Buffer);
        $Buffer = json_decode($data->Buffer);
        $this->debug(__FUNCTION__, 'Topic' . $Buffer->Topic);
        $this->debug(__FUNCTION__, 'MSG' . $Buffer->MSG);
        $this->publish($Buffer->Topic,$Buffer->MSG,0,0);
    }

    /**
     * Data Interface tp Parent (IO-TX)
     * Forward commands to IO Instance
     * @param String $Data
     * @return bool
     */
    public function onSendText(string $Data)
    {
        $res = false;
        $json = json_encode(
            array("DataID" => $this->module_interfaces['IO-TX'],
                "Buffer" => utf8_encode($Data)));
        if ($this->HasActiveParent()) {
            $this->debug(__FUNCTION__, strToHex($Data));
            $res = parent::SendDataToParent($json);
        } else {
            $this->debug(__FUNCTION__, 'No Parent');
        }
        $this->RegisterTimerNow('Ping', $this->mqtt->keepalive*1000,  'MQTTC_TimerEvent('.$this->InstanceID.');');
        return $res;

    }//function
    public function onDebug(string $topic,string $data) {
        $this->debug($topic, $data);
    }

    public function onReceive($para) {
        if($para['SENDER']=='MQTT_CONNECT'){
            $clientid=$this->GetClientID();
            IPS_LogMessage(__CLASS__,__FUNCTION__."::Connection to ClientID $clientid run");
        }

        if($para['SENDER']=='MQTT_CONNECT'){
            $this->Subscribe("#",0);
            IPS_LogMessage(__CLASS__,__FUNCTION__." Module Type Forward");
        }

        $JSON['DataID'] = "{018EF6B5-AB94-40C6-AA53-46943E824ACF}";
        $JSON['Buffer'] = json_encode($para);
        $Data = json_encode($JSON);
        //if (gettype($JSON['Buffer']) == "string") {
        $this->SendDebug("Type", $JSON['Buffer'], 0);
        $this->SendDebug('SendDataToChildren', $Data, 0);
        $this->SendDataToChildren($Data);
        //}
        /** else {
        //$this->SendDebug("Type Test",gettype($JSON['Buffer']),0);
        IPS_LogMessage("Type Test", gettype($JSON['Buffer']));
        IPS_LogMessage("Type Test", print_r($para,1));
        } **/

        
    }

    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    public function TimerEvent() {
        $this->debug(__FUNCTION__,"Timer");  // Debug Fenster            // Selbsterstellter Code
        if (!is_null($this->mqtt)){
            $this->mqtt->ping();
        }
    }
    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    public function Publish(string $topic,string $content, $qos = 0, $retain = 0) {
        if (!is_null($this->mqtt)){
            $this->mqtt->publish($topic, $content, $qos, $retain);
        }else {
            $this->debug(__FUNCTION__,"Error, Publish not possible");
        }
    }

    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    public function Subscribe(string $topic,  $qos = 0) {
        IPS_LogMessage(__CLASS__,__FUNCTION__."::Subscribe topic: ".$topic);
        if (!is_null($this->mqtt)){
            $this->mqtt->subscribe($topic, $qos);
        }else {
            $this->debug(__FUNCTION__,"Error, Subscribe nnot possible");
        }
    }

    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    private function MQTTConnect(){
        IPS_LogMessage(__CLASS__,__FUNCTION__."::Connect to client start");
        $cID=$this->GetConnectionID();

// Testweise entfernt
        // sicherstellen das die Verbindung geschlossen ist
        if($cID <> 0){
            $this->SendDebug("Connect ParentID", $cID,0);
            IF (@IPS_GetProperty($cID,"Open")){
                //IPS_SetProperty($cID, "Open", FALSE); //I/O Instanz soll aktiviert sein.
                //IPS_ApplyChanges($cID); //Neue Konfiguration übernehmen
            }
        }
        IPS_Sleep(3000);

        if(is_null($this->mqtt)){
            //$ok = @IPS_SetProperty($cID, "Open", true); //I/O Instanz soll aktiviert sein.
            $ok = true;
            if($ok){
                //$ok = @IPS_ApplyChanges($cID); //Neue Konfiguration übernehmen
                $clientid=$this->GetClientID();
                if ($ok) {
                    $username=$this->GetUser();
                    $password=$this->GetPassword();
                    $owner = $this;
                    $this->mqtt = new phpMQTT($owner,$clientid);
                    // callback Funktionen
                    $this->mqtt->onSend = 'onSendText';
                    $this->mqtt->onDebug = 'onDebug';
                    $this->mqtt->onReceive = 'onReceive';
                    $this->mqtt->debug = true;
                    if ($this->mqtt -> connect(true,null,$username,$password)) {
                        $this->debug(__FUNCTION__,"Connected to ClientID $clientid");
                        $this->OSave($this->mqtt,"MQTT");
                        IPS_Sleep(500);
                        $this->RegisterTimerNow('Ping', $this->mqtt->keepalive*1000,  'MQTTC_TimerEvent('.$this->InstanceID.');');
                    }else{
                        $ok = FALSE;
                    }
                }else{
                    IPS_LogMessage(__CLASS__,__FUNCTION__."::Connect to ClientID $clientid failed");
                    $this->debug(__FUNCTION__,"Connect to ClientID $clientid failed");
                    IPS_SetProperty($cID, "Open", false);
                    IPS_ApplyChanges($cID); //Neue Konfiguration übernehmen
                }
            }
        }
    }
    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    private function MQTTDisconnect($call=""){
        if(!is_null($this->mqtt)){
            $this->mqtt->close();
            $this->mqtt = NULL;
            $this->OSave($this->mqtt,"MQTT");
            $clientid = $this->GetClientID();
            IPS_LogMessage(__CLASS__,__FUNCTION__."::Connection to ClientID $clientid lost ($call)");
        }
        $cID=$this->GetConnectionID();
        if($cID <> 0){
            IF (IPS_GetProperty($cID,"Open")){
                //IPS_SetProperty($cID, "Open", FALSE); //I/O Instanz soll aktiviert sein.
                //IPS_ApplyChanges($cID); //Neue Konfiguration übernehmen
            }
        }
        $this->RegisterTimerNow('Ping', 0,  'MQTTC_TimerEvent('.$this->InstanceID.');');
//            if($this->GetInstanceStatus() == self::ST_AKTIV ){
//                $this->MQTTConnect();
//            }
    }
    //------------------------------------------------------------------------
    //
    //------------------------------------------------------------------------
    private function OSave($object,$name){
        if($object === NULL){
            $sClass = "";
        }else{
            $sClass = serialize($object);
        }
        $this->SetBuffer ($name,$sClass);
    }
}
?>