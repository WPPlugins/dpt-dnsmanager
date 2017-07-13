<?php

class DNSFile {
  public $dnsContents = "";
  public $records = array();
  public $ttl = 38400;
  public $debug = array();
  public $serial = 1;
  private $counter = 0;
  public $errorMessage = "";
  public $errorCode = "";
  public function SaveToSession(&$session) {
    $session[__CLASS__]["dnsContents"]=$this->dnsContents;
    $session[__CLASS__]["records"]=$this->records;
    $session[__CLASS__]["ttl"]=$this->ttl;
    $session[__CLASS__]["counter"]=$this->counter;
    $session[__CLASS__]["serial"]=$this->serial;
    $this->ReferenceFromSession($session);
  }
  private function ReferenceFromSession(&$session) {
    $this->dnsContents = &$session[__CLASS__]["dnsContents"];
    $this->records = &$session[__CLASS__]["records"];
    $this->ttl = &$session[__CLASS__]["ttl"];
    $this->counter = &$session[__CLASS__]["counter"];
    $this->serial = &$session[__CLASS__]["serial"];
  }
  public static function LoadFromSessionData(&$session) {
    if(!isset($session[__CLASS__]))
      return false;
    $dnsFile = new DNSFile();
    $dnsFile->ReferenceFromSession($session);
    return $dnsFile;
  }
  public static function Open($fileLocation) {
    $dnsFile = new DNSFile();
    $dnsFile->ParseString(file_get_contents($fileLocation));
    return $dnsFile;
  }
  public function ToBindString() {
    $result = "\$ttl ".$this->ttl."\r\n";
    ksort($this->records);
    foreach($this->records as $key=>$record) {
      switch($record["type"]) {
        case "IN SOA":
          $record["domain_email"] .= " (\r\n\t";
          $record["serial"] = $this->serial."\r\n\t";
          $record["refresh"] .= "\r\n\t";
          $record["retry"] .= "\r\n\t";
          $record["expire"] .= "\r\n\t";
          $record["negative_response_ttl"] .= " )";
          $result .= implode(" ", $record)."\r\n";
          break;
        case "IN A":
        case "IN CNAME":
        case "IN MX":
        case "IN NS":
          $result .= implode(" ", $record)."\r\n";
          break;
      }
    }
    return $result;
  }
  public function AddARecord($host, $ip, $ttl=null) {
    $this->records[(400+$this->counter++)."_A"] = array(
//    array_push($this->records, array(
      "host"=>$host
      , "type"=>"IN A"
      , "destination_ip"=>$ip
//      , "ttl"=>$ttl
//    )
    );
    $this->serial++;
    return true;
  }
  public function EditARecord($id, $host, $ip, $ttl=null) {
    $this->records[$id] = array(
//    array_push($this->records, array(
      "host"=>$host
      , "type"=>"IN A"
      , "destination_ip"=>$ip
//      , "ttl"=>$ttl
//    )
    );
    $this->serial++;
    return true;
  }
  public function DeleteRecord($id) {
    unset($this->records[$id]);
    $this->serial++;
    return true;
  }
  public function AddMXRecord($host, $domain, $priority=10, $ttl=null) {
    $this->records[(300+$this->counter++)."_MX"] = array(
      "host"=>$host
      , "type"=>"IN MX"
      , "priority"=>$priority
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
    );
    $this->serial++;
    return true;
  }
  public function EditMXRecord($id, $host, $domain, $priority=10, $ttl=null) {
    $this->records[$id] = array(
      "host"=>$host
      , "type"=>"IN MX"
      , "priority"=>$priority
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
    );
    $this->serial++;
    return true;
  }
  public function AddNSRecord($host, $domain, $ttl=null) {
    $this->records[(200+$this->counter++)."_NS"] = array(
      "host"=>$host
      , "type"=>"IN NS"
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
    );
    $this->serial++;
    return true;
  }
  public function EditNSRecord($id, $host, $domain, $ttl=null) {
    $this->records[$id] = array(
      "host"=>$host
      , "type"=>"IN NS"
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
    );
    $this->serial++;
    return true;
  }
  private function FullDomainName($name, $origin, $prev_name="") {
    if($name=="") {
      $name = $prev_name;
    }
    if($name[strlen($name)-1]==='.') {
      return $name;
    }
    return $name.$origin;
  }
  public function AddSOARecord($host, $primary_ns, $domain_email, $serial, $refresh, $retry, $expire, $negativeResponseTTL) {
    if(isset($this->records["100_SOA"])) {
      $this->errorMessage = "An SOA record already exists.";
      $this->errorCode = "ALREADY_EXISTS";
      return false;
    }
    $this->records["100_SOA"] = array(
      "host"=>$host
      , "type"=>"IN SOA"
      , "primary_ns"=>$primary_ns
      , "domain_email"=>$domain_email
      , "serial"=> $serial
      , "refresh"=>$refresh
      , "retry"=>$retry
      , "expire"=>$expire
      , "negative_response_ttl"=>$negativeResponseTTL
    );
    $this->serial = $serial;
    return true;
  }
  public function EditSOARecord($id, $host, $primary_ns, $domain_email, $serial, $refresh, $retry, $expire, $negativeResponseTTL) {
    $this->records["100_SOA"] = array(
      "host"=>$host
      , "type"=>"IN SOA"
      , "primary_ns"=>$primary_ns
      , "domain_email"=>$domain_email
      , "serial"=> $serial
      , "refresh"=>$refresh
      , "retry"=>$retry
      , "expire"=>$expire
      , "negative_response_ttl"=>$negativeResponseTTL
    );
    $this->serial = $serial;
    return true;
  }
  public function AddCNAMERecord($host, $domain, $ttl=null) {
    $this->records[(400+$this->counter++)."_CNAME"] = array(
//    array_push($this->records, array(
      "host"=>$host
      , "type"=>"IN CNAME"
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
//    )
    );
    $this->serial++;
    return true;
  }
  public function EditCNAMERecord($id, $host, $domain, $ttl=null) {
    $this->records[$id] = array(
//    array_push($this->records, array(
      "host"=>$host
      , "type"=>"IN CNAME"
      , "destination_domain"=>$domain
//      , "ttl"=>$ttl
//    )
    );
    $this->serial++;
    return true;
  }
  protected function ParseString($dnsContents) {
    $this->dnsContents = $dnsContents;
    $serial = 1;
    $dnsContents = str_replace(array("\r\n", "\t"), array("\n", " "), $dnsContents);
    $rows = explode("\n", $dnsContents);
    $ORIGIN = ".";
    $host_if_blank = "";
    for($i=0; $i<count($rows); $i++) {
      $row = $rows[$i];
      $ttl = $this->ttl;
      $columns = explode(' ', $row);
      if($row[0]===';') {
        //comment line, let's ignore
        continue;
      }
      if($row[0]==='$') {
        //variable assignment
        switch(strtoupper($columns[0])) {
          case "\$ORIGIN":
            $ORIGIN = ".".$columns[1];
            break;
          case "\$TTL":
            $this->ttl = $columns[1];
            break;
        }
        continue;
      }
      if(is_array($this->debug))
        array_push($this->debug, array(
          "message"=>"let's see if this is not a ttl line"
          , "condition"=>strtoupper($columns[1])
        ));
      $IN_index = -1;
      if(strtoupper($columns[1])=="IN") {
        $IN_index = 1;
      }
      else if(strtoupper($columns[2])=="IN") {
        $IN_index=2;
      }
      if($IN_index>0) {
        if(is_array($this->debug))
          array_push($this->debug, array(
            "message"=>"switch statement"
            , "condition"=>strtoupper($columns[$IN_index+1])
          ));
        if($columns[0]==="\\042") {
          $columns[0]="*";
        }
        switch(strtoupper($columns[$IN_index+1])) {
          case "MX":
            $ttl = empty($columns[$IN_index+4])?null:$columns[$IN_index+4];
            $this->AddMXRecord($this->FullDomainName($columns[0], $ORIGIN, $host_if_blank), $columns[$IN_index+3], $columns[$IN_index+2], $ttl);
            break;
          case "CNAME":
            $ttl = empty($columns[$IN_index+3])?null:$columns[$IN_index+3];
            $this->AddCNAMERecord($this->FullDomainName($columns[0], $ORIGIN, $host_if_blank), $this->FullDomainName($columns[$IN_index+2], $ORIGIN), $ttl);
            break;
          case "SOA":
            //because there's brackets in this instance we have to reparse this differently
            $bracketPortion="";
            $pos=false;
            $j=0;
            //look for start of bracket
            while(!$pos && count($rows)>($i+$j)) {
              //get the remainder part of the line
              $pos = strrpos($rows[$i+$j], '(');
              if(is_array($this->debug))
                array_push($this->debug, array(
                  "message"=>"SOA looking for starting bracket"
                  , "row"=>$rows[$i+$j]
                  , "pos"=>$pos
                ));
              $j++;
            }
            $j--;
            //if there's no starting bracket it means it will be in subsequent rows
            while(!strrpos($rows[$i+$j], ')') && count($rows)>($i+$j)) {
              $bracketPortion .= $rows[$i+$j];
              $j++;
            }
            $bracketPortion .= $rows[$i+$j];
//            preg_match('/\((.*?)\)/', $row, $bracketPortion);
//            $bracketPortion = trim($bracketPortion[0],"() ");
            preg_match('/\((.*?)\)/', $bracketPortion, $matches);
            $bracketPortion = trim($matches[0],"() ");
            $bracketPortion = preg_replace('/\s+/'," ", $bracketPortion);
            if(is_array($this->debug))
              array_push($this->debug, array(
                "message"=>"SOA params"
                , "params"=>$bracketPortion
              ));
            $SOAParams = explode(" ", $bracketPortion);
            $this->AddSOARecord($this->FullDomainName($columns[0], $ORIGIN, $host_if_blank), $this->FullDomainName($columns[$IN_index+2], $ORIGIN), $columns[$IN_index+3], $SOAParams[0], $SOAParams[1], $SOAParams[2], $SOAParams[3], $SOAParams[4]);
            $serial = $SOAParams[0];
            break;
          case "A":
            $ttl = empty($columns[$IN_index+3])?null:$columns[$IN_index+3];
            $this->AddARecord($this->FullDomainName($columns[0], $ORIGIN, $host_if_blank), $columns[$IN_index+2], $ttl);
            break;
          case "NS":
            $ttl = empty($columns[$IN_index+3])?null:$columns[$IN_index+3];
            $this->AddNSRecord($this->FullDomainName($columns[0], $ORIGIN, $host_if_blank), $this->FullDomainName($columns[$IN_index+2], $ORIGIN), $ttl);
            break;
        }
        if($columns[0]!="") {
          $host_if_blank = $columns[0];
        }
      }
    }
    $this->serial = $serial;
  }
}
