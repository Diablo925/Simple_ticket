<?php

class module_controller extends ctrl_module
{

		static $ok;
		
    /**
     * The 'worker' methods.
     */
	
	static function doread()
    {
		global $controller;
        runtime_csfr::Protect();
		$currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
        //return self::ExectuteRead($formvars['innumber']);
		if (isset($formvars['inRead'])) {
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=read&ticket='. $formvars['innumber']. '');
                exit;
            }
			 return true;
	}
	
	static function doselect()
    {
        global $controller;
        runtime_csfr::Protect();
        $currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		
            if (isset($formvars['inMyTicket'])) {
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=MyTicket');
                exit;
            }
			if (isset($formvars['inNewTicket'])) {
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=NewTicket');
                exit;
            }
        return true;
    }
	
	static function getisMyTicket()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "MyTicket");
    }
	
	static function getisread()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "read");
    }
	
	static function getisNewTicket()
    {
        global $controller;
        $urlvars = $controller->GetAllControllerRequests('URL');
        return (isset($urlvars['show'])) && ($urlvars['show'] == "NewTicket");
    }

	static function ListSelectTicket($uid)
	{
		global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$urlvars = $controller->GetAllControllerRequests('URL');
		$ticket = $urlvars['ticket'];
		$sql = "SELECT * FROM x_ticket WHERE st_acc_id = :uid AND st_number = :number";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
		$numrows->bindParam(':number', $ticket);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
			$sql->bindParam(':number', $ticket);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch()) {
                array_push($res, array('Ticket_number' => $row['st_number'], 'Ticket_domain' => $row['st_domain'],
										'Ticket_subject' => $row['st_subject'], 'Ticket_msg' => $row['st_meassge'], 'Ticket_answers' => $row['st_ticketanswers']));
            }
            return $res;
        } else {
            return false;
        }
		
	}
	
   	static function ListDomain($uid)
    {
        global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
        $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk = :uid AND vh_deleted_ts IS NULL";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch()) {
                array_push($res, array('dname' => $row['vh_name_vc']));
            }
            return $res;
        } else {
            return false;
        }
    }
	
	static function ListTicket($uid)
    {
		global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$sql = "SELECT * FROM x_ticket WHERE st_acc_id = :uid";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
		$sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch()) {
                array_push($res, array('ticketid' => $row['st_id'], 'ticketnumber' => $row['st_number'], 'ticketdomain' => $row['st_domain'], 'ticketsubject' => $row['st_subject'], 'ticketdate' => $row['st_ticketdate'], 'ticketstatus' => $row['st_ticketstatus']));
            }
            return $res;
        } else {
            return false;
        }
	} 
	
    /**
     * End 'worker' methods.
     */

    /**
     * Webinterface sudo methods.
     */

	static function getTicket()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListSelectTicket($currentuser['userid']);
    }
	
    static function getDomainList()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListDomain($currentuser['userid']);
    }
	
	static function getTicketList()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListTicket($currentuser['userid']);
    } 
	
	 static function doSend()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        return self::ExectuteSend($formvars['inPackage'], $formvars['inSubject'], $formvars['inMessage']);
    }
	
	static function getResult()
    {
		 if (self::$ok) {
            return ui_sysmessage::shout(ui_language::translate("Your mail has been sent"), "zannounceok");
        }
        return;
    }

    /**
     * Webinterface sudo methods.
     */
}