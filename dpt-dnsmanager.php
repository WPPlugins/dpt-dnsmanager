<?php
/*
Plugin Name: Wordpress DNS Manager by DigitialPixies
Plugin URI: http://wordpress.digitalpixies.com/dpt-dnsmanager
Description: Create and Manage a bind compatible dns file. Designed for intranet wordpress instances (not public facing).
Version: 1.2.1
Author: Robert Huie
Author URI: http://DigitalPixies.com
License: GPLv2
*/

if(!class_exists("dpt_dnsmanager_php")) {
  class dpt_dnsmanager_php {
    public $data = null;
    private $DNSFile = null;
    private $debugLevel = 1;
    private $NONPUBLIC_WRITABLE_FOLDER = null;
    private $HOSTFILE = "dpt-dnsmanager.host";
    private $log = null; //array();
    public function GetDNSFile($forceReload=false) {
      if($forceReload || empty($this->DNSFile)) {
        include_once("includes/DNSFile.Class.php");
        if($forceReload || !($this->DNSFile=DNSFile::LoadFromSessionData($_SESSION))) {
          $this->DNSFile = DNSFile::Open($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE);
          $this->DNSFile->SaveToSession($_SESSION);
        }
      }
      return $this->DNSFile;
    }
    private function ReloadNSDC() {
      $result["details"]["nsd3_runnable"]["info"]="Test running nsdc's rebuild function";
      $result["details"]["nsd3_runnable"]["result"]=shell_exec('sudo /usr/sbin/nsdc rebuild 2>&1');
      $result["details"]["nsd3_runnable"]["expected_result"]=null;
      $result["details"]["nsd3_runnable"]["pass"]=$result["details"]["nsd3_runnable"]["result"]===$result["details"]["nsd3_runnable"]["expected_result"];
      $result["details"]["nsd3_runnable"]["alert_type"]=$result["details"]["nsd3_runnable"]["pass"]?"success":"danger";
      if(!$result["details"]["nsd3_runnable"]["pass"]) {
        return array("message"=>"Problems rebuilding"
          ,"success"=>false);
      }
      $result["details"]["nsd3_reload_runnable"]["info"]="Test running nsdc's reload function";
      $result["details"]["nsd3_reload_runnable"]["result"]=shell_exec('sudo /usr/sbin/nsdc reload 2>&1');
      $result["details"]["nsd3_reload_runnable"]["expected_result"]=null;
      $result["details"]["nsd3_reload_runnable"]["pass"]=$result["details"]["nsd3_reload_runnable"]["result"]===$result["details"]["nsd3_reload_runnable"]["expected_result"];
      $result["details"]["nsd3_reload_runnable"]["alert_type"]=$result["details"]["nsd3_reload_runnable"]["pass"]?"success":"danger";
      if(!$result["details"]["nsd3_reload_runnable"]["pass"]) {
        return array("message"=>"Problems reloading"
          ,"success"=>false);
      }
      return array("success"=>true);
    }
    public function dpt_dnsmanager_php() {
      $this->RegisterHooks();
    }
    public function RegisterHooks() {
      add_action('init', array($this, 'Initialize'));
      add_action('admin_menu', array($this, 'AdminMenu'));
      add_action('admin_enqueue_scripts', array($this, 'EnableCSSJS'));
      //prevent admin ajax from being exposed to non admins
      if(is_admin()) {
        add_action('wp_ajax_dpt-dnsmanager', array($this, 'AJAX'));
      }
    }
    public function AJAXSystemCheck() {
      $result["HTML"]="";

      $result["details"]["writefolder"]["info"]="Get info about the folder we want write access to";
      $result["details"]["writefolder"]["alert_type"]="info";
      $result["details"]["writefolder"]["result"]=$this->NONPUBLIC_WRITABLE_FOLDER;

      $result["details"]["writeaccess"]["info"]="Determine if we have write access";
      $result["details"]["writeaccess"]["pass"]=is_writable($this->NONPUBLIC_WRITABLE_FOLDER);
      $owner = posix_getgrgid(fileowner($this->NONPUBLIC_WRITABLE_FOLDER));
      $owner = $owner["name"];
      $group = posix_getgrgid(filegroup($this->NONPUBLIC_WRITABLE_FOLDER));
      $group = $group["name"];
      $result["details"]["writeaccess"]["result"]="folder: ".$this->NONPUBLIC_WRITABLE_FOLDER."<br/>"
        ."Currently has the following attributes:<br/>"
        ."owner: ".$owner."<br/>"
        ."group: ".$group."<br/>"
        ."permissions: ".decoct(fileperms($this->NONPUBLIC_WRITABLE_FOLDER) & 0777)."<br/>";

      $result["details"]["writeaccess"]["alert_type"]=$result["details"]["writeaccess"]["pass"]?"success":"danger";

      $result["details"]["whoami"]["info"]="Get the currently running user";
      $result["details"]["whoami"]["result"]=shell_exec('whoami 2>&1');
      $result["details"]["whoami"]["expected_result"]=array("www-data", "http");
      $result["details"]["whoami"]["pass"]=$result["details"]["whoami"]["result"]!=null;
      $result["details"]["whoami"]["alert_type"]=$result["details"]["whoami"]["pass"]?"success":"warning";
/*
      $result["details"]["bind9"]["info"]="Test running bind9 to see the valid parameters";
      $result["details"]["bind9"]["result"]=shell_exec('sudo /etc/init.d/bind9 2>&1');
      $result["details"]["bind9"]["expected_result"]=" * Usage: /etc/init.d/bind9 {start|stop|reload|restart|force-reload|status}\n";
      $result["details"]["bind9"]["pass"]=$result["details"]["bind9"]["expected_result"]==$result["details"]["bind9"]["result"];
      $result["details"]["bind9"]["alert_type"]=$result["details"]["bind9"]["pass"]?"success":"warning";

      $result["details"]["bind9_runnable"]["info"]="Test running bind9's reload function'";
      $result["details"]["bind9_runnable"]["result"]=shell_exec('sudo /etc/init.d/bind9 force-reload 2>&1');
      $result["details"]["bind9_runnable"]["expected_result"]="";
      $result["details"]["bind9_runnable"]["pass"]=$result["details"]["bind9_runnable"]["expected_result"]==$result["details"]["bind9_runnable"]["result"];
      $result["details"]["bind9_runnable"]["alert_type"]=$result["details"]["bind9_runnable"]["pass"]?"success":"warning";
*/
      $result["details"]["nsd3"]["info"]="Test running nsdc to see the valid parameters";
      $result["details"]["nsd3"]["result"]=shell_exec('sudo /usr/sbin/nsd-control 2>&1');
      $result["details"]["nsd3"]["expected_result"]="Usage:	nsd-control [options] command";
      $result["details"]["nsd3"]["pass"]=stripos($result["details"]["nsd3"]["result"],$result["details"]["nsd3"]["expected_result"])!==false;
      $result["details"]["nsd3"]["alert_type"]=$result["details"]["nsd3"]["pass"]?"success":"warning";

      $result["details"]["nsd3_runnable"]["info"]="Test running nsd-control's reconfig function";
      $result["details"]["nsd3_runnable"]["result"]=shell_exec('sudo /usr/sbin/nsd-control reconfig 2>&1');
      $result["details"]["nsd3_runnable"]["expected_result"]="reconfig start, read /etc/nsd/nsd.conf\nok\n";
      $result["details"]["nsd3_runnable"]["pass"]=$result["details"]["nsd3_runnable"]["result"]==$result["details"]["nsd3_runnable"]["expected_result"];
      $result["details"]["nsd3_runnable"]["alert_type"]=$result["details"]["nsd3_runnable"]["pass"]?"success":"danger";

      $result["details"]["nsd3_reload_runnable"]["info"]="Test running nsd-control's reload function";
      $result["details"]["nsd3_reload_runnable"]["result"]=shell_exec('sudo /usr/sbin/nsd-control reload 2>&1');
      $result["details"]["nsd3_reload_runnable"]["expected_result"]="ok\n";
      $result["details"]["nsd3_reload_runnable"]["pass"]=$result["details"]["nsd3_reload_runnable"]["result"]===$result["details"]["nsd3_reload_runnable"]["expected_result"];
      $result["details"]["nsd3_reload_runnable"]["alert_type"]=$result["details"]["nsd3_reload_runnable"]["pass"]?"success":"danger";

      $result["pass"]=false;

      $weight = 0;
      foreach($result["details"] as $key=>$details) {
        $result["details"][$key]["weight"] = ++$weight;
      }

      return $result;
    }
    public function AJAX() {
      global $wpdb;
      switch($_REQUEST["sub_action"]) {
        case "DeleteDNSFile":
          $result["successful"] = unlink($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE);
          $result["records"] = $this->GetDNSFile(true)->records;
          break;
        case "SaveAndRestart":
          $result["bytesWritten"] = file_put_contents($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE, $this->GetDNSFile()->ToBindString());
          $result["records"] = $this->GetDNSFile()->records;
          $reloadResult = $this->ReloadNSDC();
          $result["success"] = $reloadResult["success"];
          break;
        case "SaveToFile":
          $result["bytesWritten"] = file_put_contents($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE, $this->GetDNSFile()->ToBindString());
          $result["records"] = $this->GetDNSFile()->records;
          break;
        case "ReloadDNSFile":
          $result["records"] = $this->GetDNSFile(true)->records;
          $result["debug"] = $this->GetDNSFile()->debug;
          break;
        case "GetRecords":
          $result["records"] = $this->GetDNSFile()->records;
          break;
        case "DeleteRecord":
          if(isset($_REQUEST["id"])) {
            $result["records"] = $this->GetDNSFile()->DeleteRecord($_REQUEST["id"]);
          }
          $result["records"] = $this->GetDNSFile()->records;
          break;
        case "AddRecord":
          if(is_array($_REQUEST["form_values"])) {
            $form = &$_REQUEST["form_values"];
            switch(strtoupper($form["type"])) {
              case "IN NS":
                $result["successful"] = $this->GetDNSFile()->AddNSRecord($form["host"], $form["destination_domain"], $form["ttl"]);
                break;
              case "IN MX":
                $result["successful"] = $this->GetDNSFile()->AddMXRecord($form["host"], $form["destination_domain"], $form["priority"], $form["ttl"]);
                break;
              case "IN CNAME":
                $result["successful"] = $this->GetDNSFile()->AddCNAMERecord($form["host"], $form["destination_domain"], $form["ttl"]);
                break;
              case "IN A":
                $result["successful"] = $this->GetDNSFile()->AddARecord($form["host"], $form["destination_ip"], $form["ttl"]);
                break;
              case "IN SOA":
                $result["successful"] = $this->GetDNSFile()->AddSOARecord($form["host"], $form["primary_ns"], $form["domain_email"], $form["serial"], $form["refresh"], $form["retry"], $form["expire"], $form["negative_response_ttl"]);
                if(!$result["successful"]) {
                  $result["error_msg"] = $this->GetDNSFile()->errorMessage;
                  $result["error_code"] = $this->GetDNSFile()->errorCode;
                }
                break;
            }
            $result["records"] = $this->GetDNSFile()->records;
          }
          break;
        case "UpdateRecord":
          if(is_array($_REQUEST["form_values"])) {
            $form = &$_REQUEST["form_values"];
            switch(strtoupper($form["type"])) {
              case "IN NS":
                $result["successful"] = $this->GetDNSFile()->EditNSRecord($form["id"], $form["host"], $form["destination_domain"], $form["ttl"]);
                break;
              case "IN MX":
                $result["successful"] = $this->GetDNSFile()->EditMXRecord($form["id"], $form["host"], $form["destination_domain"], $form["priority"], $form["ttl"]);
                break;
              case "IN CNAME":
                $result["successful"] = $this->GetDNSFile()->EditCNAMERecord($form["id"], $form["host"], $form["destination_domain"], $form["ttl"]);
                break;
              case "IN A":
                $result["successful"] = $this->GetDNSFile()->EditARecord($form["id"], $form["host"], $form["destination_ip"], $form["ttl"]);
                break;
              case "IN SOA":
                $result["successful"] = $this->GetDNSFile()->EditSOARecord($form["id"], $form["host"], $form["primary_ns"], $form["domain_email"], $form["serial"], $form["refresh"], $form["retry"], $form["expire"], $form["negative_response_ttl"]);
                if(!$result["successful"]) {
                  $result["error_msg"] = $this->GetDNSFile()->errorMessage;
                  $result["error_code"] = $this->GetDNSFile()->errorCode;
                }
                break;
            }
            $result["records"] = $this->GetDNSFile()->records;
          }
          break;
        case "DownloadDNSFile":
          header('Content-Type: text/plain');
          header("Content-disposition: attachment; filename=\"dns.export.host\"");
          include_once("includes/DNSFile.Class.php");
          $DNSFile = DNSFile::Open($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE);
          print $DNSFile->dnsContents;
          wp_die();
          break;
        case "ViewDNSFile":
          include_once("includes/DNSFile.Class.php");
          $DNSFile = DNSFile::Open($this->NONPUBLIC_WRITABLE_FOLDER."/".$this->HOSTFILE);
          $result["HTML"] = $DNSFile->dnsContents;
          break;
        default:
          $result = $this->AJAXSystemCheck();
          break;
      }
      header('Content-Type: application/json');
      print json_encode($result);
      wp_die();
    }
    public function EnableCSSJS($hook) {
if($this->debugLevel>7 && is_array($this->log))
  array_push($this->log, array(
    "function"=>__METHOD__
    , "param"=>$hook
  ));
      if($hook != "toplevel_page_dpt_dnsmanager_php")
        return;
      wp_register_style('localizedbootstrap', plugin_dir_url(__FILE__).'includes/css/localizedbootstrap.css');
      wp_register_style('localizedbootstrap', plugins_url('includes/css/localizedbootstrap-theme.css', __FILE__));
      wp_enqueue_style('localizedbootstrap');

      wp_register_style('dnsmanager', plugin_dir_url(__FILE__).'includes/css/dns.crud.css');
      wp_enqueue_style('dnsmanager');

      wp_register_script('angular-ui', plugin_dir_url(__FILE__).'includes/js/vendor.js', array(), "2.5.0", true);
      wp_enqueue_script('angular-ui');

			wp_register_script('dnsmanager', plugin_dir_url(__FILE__).'includes/js/scripts.js', array("angular-ui"), "2.5.0", true);
			wp_enqueue_script('dnsmanager');
			$params['ajax_url']=admin_url('admin-ajax.php');
			wp_localize_script('dnsmanager', 'wordpress', $params);
    }
    public function AdminMenu() {
      $result = add_menu_page('Wordpress DNS Manager by DigitalPixies', 'Manage DNS', 'manage_options', __CLASS__, array($this, 'AdminHTML'));
if($this->debugLevel>7 && is_array($this->log))
  array_push($this->log, array(
    "function"=>__METHOD__
    , "add_menu_page()"=>$result
  ));
    }
    public function AdminHTML() {
      print file_get_contents(dirname(__FILE__).'/dnsmanager.crud.html');
if(is_array($this->log))
  print "<pre>".htmlentities(print_r($this->log, true))."</pre>";
    }
    public function Initialize() {
//      define('WP_DEBUG', true);
      if(defined("NONPUBLIC_WRITABLE_FOLDER")) {
        $this->NONPUBLIC_WRITABLE_FOLDER = constant("NONPUBLIC_WRITABLE_FOLDER");
      }
      else {
        $this->NONPUBLIC_WRITABLE_FOLDER = wp_upload_dir();
        $this->NONPUBLIC_WRITABLE_FOLDER = $this->NONPUBLIC_WRITABLE_FOLDER['basedir']."/dpt-dnsmanager";
        if(!file_exists($this->NONPUBLIC_WRITABLE_FOLDER))
          mkdir($this->NONPUBLIC_WRITABLE_FOLDER);
      }
if($this->debugLevel>8 && is_array($this->log))
  array_push($this->log, array(
    'function'=>__METHOD__
    , 'message'=>"determined write folder location"
    , 'NONPUBLIC_WRITABLE_FOLDER'=>$this->NONPUBLIC_WRITABLE_FOLDER
        ));
      if(!is_null($this->data))
        return;
      session_start();
      if(!isset($_SESSION[__CLASS__]))
        $_SESSION[__CLASS__]=array(
          );
      $this->data=&$_SESSION[__CLASS__];
    }
  }
}

$dpt_dnsmanager_php = new dpt_dnsmanager_php();
