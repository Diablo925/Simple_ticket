<?php

class module_controller extends ctrl_module
{

		static $ok;
		static $update;
		
    /**
     * The 'worker' methods.
     */
	
	static function doread()
    {
		global $controller;
        runtime_csfr::Protect();
		$currentuser = ctrl_users::GetUserDetail();
        $formvars = $controller->GetAllControllerRequests('FORM');
		if (isset($formvars['inRead'])) {
                header("location: ./?module=" . $controller->GetCurrentModule() . '&show=read&ticket='. $formvars['innumber']. '');
                exit;
            }
			 return true;
	}
	
	static function ExectuteSendTicket($domain, $subject, $msg)
	{
		
		global $zdbh;
		global $controller;
		$length = 4;
		$ticketnumber = fs_director::GenerateRandomPassword($length, 4);
		$date = date("Y-m-d");
		$ticketid = "$date.$ticketnumber"; 
		$Ticketstatus = "Open";
		$currentuser = ctrl_users::GetUserDetail();
		
		$sql = "SELECT * FROM x_accounts WHERE ac_id_pk = :uid";
		$sql = $zdbh->prepare($sql);
        $sql->bindParam(':uid', $currentuser['userid']);
		$sql->execute();
        while ($row = $sql->fetch()) { $reseller = $row["ac_reseller_fk"]; }
		$sql = $zdbh->prepare("INSERT INTO x_ticket (st_acc, st_number, st_domain, st_subject, st_meassge, st_status, st_groupid) VALUES (:uid, :number, :domain, :subject, :msg, :ticketstatus, :group)");
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->bindParam(':number', $ticketid);
		$sql->bindParam(':domain', $domain);
		$sql->bindParam(':subject', $subject);
		$sql->bindParam(':msg', $msg);
		$sql->bindParam(':ticketstatus', $Ticketstatus);
		$sql->bindParam(':group', $reseller);
        $sql->execute();
        self::$ok = true;
		return true;
	}
	static function ExectuteTicketUpdate($msg, $ticketid)
	{
		global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$date = date("Y-m-d - H:i:s");
		
		$sql_old= "SELECT * FROM x_ticket WHERE st_number = :number AND st_acc = :uid";
		$sql_old = $zdbh->prepare($sql_old);
            $sql_old->bindParam(':uid', $currentuser['userid']);
			$sql_old->bindParam(':number', $ticketid);
            $sql_old->execute();
            while ($row_old = $sql_old->fetch()) {
				$oldmsg = $row_old["st_meassge"];
			}
		
		$msg = "$oldmsg \n\n $date -- $msg \n\n ---------------------------------- \n\n";
		
		$sql = $zdbh->prepare("UPDATE x_ticket SET st_meassge = :msg WHERE st_number = :number AND st_acc = :uid");
		$sql->bindParam(':uid', $currentuser['userid']);
		$sql->bindParam(':number', $ticketid);
		$sql->bindParam(':msg', $msg);
        $sql->execute();
		
		$sql_user = "SELECT * FROM x_ticket WHERE st_accpid = :uid AND st_number = :number";
		$sql_user = $zdbh->prepare($sql_user);
            $sql_user->bindParam(':uid', $currentuser['userid']);
			$sql_user->bindParam(':number', $ticketid);
            $sql_user->execute();
            while ($row_user = $sql_user->fetch()) {
				$userid = $row_user["st_groupid"];
			}
			
			$sql_user1 = "SELECT * FROM x_accounts WHERE ac_id_pk = :uid";
		$sql_user1 = $zdbh->prepare($sql_user1);
            $sql_user1->bindParam(':uid', $userid);
			$sql_user1->bindParam(':number', $ticketid);
            $sql_user1->execute();
            while ($row1 = $sql_user1->fetch()) {
				$mail = $row1["ac_email_vc"];
				$name = $row1["ac_user_vc"];
			}
		
		    $email = $mail;
			$emailsubject = "$ticketid -- The ticket has been updatet ";
            $emailbody = "Hi $name\n\n $msg";
		

            $phpmailer = new sys_email();
            $phpmailer->Subject = $emailsubject;
            $phpmailer->Body = $emailbody;
            $phpmailer->AddAddress($email);
            $phpmailer->SendEmail();
			
			self::$update = true;
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
		$sql = "SELECT * FROM x_ticket WHERE st_acc = :uid AND st_number = :number";
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
		$sql = "SELECT * FROM x_ticket WHERE st_acc = :uid";
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $currentuser['userid']);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
		$sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $currentuser['userid']);
            $res = array();
            $sql->execute();
            while ($row = $sql->fetch()) {
                array_push($res, array('ticketid' => $row['st_id'], 'ticketnumber' => $row['st_number'], 'ticketdomain' => $row['st_domain'], 'ticketsubject' => $row['st_subject'], 'ticketstatus' => $row['st_status']));
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
	
	static function doSendTicket()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        if (self::ExectuteSendTicket($formvars['inDomain'], $formvars['inSubject'], $formvars['inMessage']));
	}
	
	static function doUpdateTicket()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        if (self::ExectuteTicketUpdate($formvars['inMessage'], $formvars['innumber']));
	}
	
	static function getResult()
    {
		 if (self::$ok) {
            return ui_sysmessage::shout(ui_language::translate("Your ticket has been created. We will look at it as soon as possible"), "zannounceok");
        }
		if (self::$update) {
            return ui_sysmessage::shout(ui_language::translate("You ticket has been update"), "zannounceok");
        }
        return;
    }

    /**
     * Webinterface sudo methods.
     */
}
?>