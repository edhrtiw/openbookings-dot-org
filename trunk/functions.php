<?php

	/* OpenBookings.org - Copyright (C) 2005 J�r�me ROGER (jerome@openbookings.org)

	functions.php - This file is part of OpenBookings.org (http://www.openbookings.org)

    OpenBookings.org is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    OpenBookings.org is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OpenBookings.org; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA */

	$application_access_level = param_extract("application_access_level");

	if(isset($_COOKIE["bookings_date_format"])) { $date_format = $_COOKIE["bookings_date_format"]; } else { $date_format = param_extract("default_date_format"); }
	if(isset($_COOKIE["bookings_time_offset"])) { $time_offset = $_COOKIE["bookings_time_offset"]; } else { $time_offset = 0; }

	// refeshes the cookie (resets timeout)
	if(isset($_COOKIE["bookings_user_id"])) {
		$session_timeout = param_extract("session_timeout");
		if($session_timeout == "0") { $session_timeout = "604800"; } // cookie never expires (in fact it does after one week)
		setcookie("bookings_user_id", $_COOKIE["bookings_user_id"], (time() + $session_timeout));
		if(isset($_COOKIE["bookings_profile_id"])) { setcookie("bookings_profile_id", $_COOKIE["bookings_profile_id"], (time() + $session_timeout)); }
		if(isset($_COOKIE["bookings_language"])) { setcookie("bookings_language", $_COOKIE["bookings_language"], (time() + $session_timeout)); }
		if(isset($_COOKIE["bookings_time_offset"])) { setcookie("bookings_time_offset", $_COOKIE["bookings_time_offset"], (time() + $session_timeout)); }
		if(isset($_COOKIE["bookings_date_format"])) { setcookie("bookings_date_format", $date_format, (time() + $session_timeout)); }
	}

	// extracts colors from the table which holds parameters
	$app_title = param_extract("app_title");

	function includeCommonScripts() {
		$common_scripts = "function $(id) { return document.getElementById(id); }\n";
		echo $common_scripts;
	}

	function dateRange($start_date, $end_date) {

		global $date_format;

		if(date("Y-m-d", strtotime($start_date)) == date("Y-m-d", strtotime($end_date))) {
			// current booking starts and ends in the same day
			return date("H:i", strtotime($start_date)) . " - " . date("H:i", strtotime($end_date));
		} else {
			// current booking stars and ends in a different day
			return date($date_format . " H:i", strtotime($start_date)) . " - " . date($date_format . " H:i", strtotime($end_date));
		}
	}

	function unDuplicateName($first_name, $last_name) {
		if($first_name == $last_name) {
			return $last_name;
		} elseif ($first_name == "") {
			return $last_name;
		} elseif ($last_name == "") {
			return $first_name;
		} else {
			return $first_name . " " . $last_name;
		}
	}

	function getGenericPermissions($profile_id, $object_id) {

		global $database_name;

		$sql = "SELECT permission FROM rs_data_permissions WHERE object_id = " . $object_id . " AND profile_id = " . $profile_id . ";";
		$permissions = db_query($database_name, $sql, "no", "no");
		if($permissions_ = fetch_array($permissions)) { return $permissions_["permission"]; } else { return "none"; }
	}

	function validateInput($input_label, $untrusted_value, $awaited_type, $min_length, $max_length) {

		// conversion to regular charset (avoids attacks by charset modification)
		$input_value = htmlentities($untrusted_value, ENT_QUOTES, "ISO-8859-1", false);

		if($min_length != 0 && strlen($input_value) < $min_length) { $error = Translate("'%l' is too short", 1); }
		if($max_length != 0 && strlen($input_value) > $max_length) { $error = Translate("'%l' is too long", 1); }

		if($error != "") {

			switch($awaited_type) {

				case "boolean":
				$input_value = filter_var($input, FILTER_VALIDATE_BOOLEAN);
				break;

				case "int":
				$input_value = filter_var($input, FILTER_VALIDATE_INT);
				break;

				case "float":
				$input = filter_var($input, FILTER_VALIDATE_FLOAT);
				break;

				case "string":
				break;

				case "url":
				$input_value = filter_var($input, FILTER_VALIDATE_URL);
				break;

				case "email":
				$input_value = filter_var($input, FILTER_VALIDATE_EMAIL);
			}

			// returns an array with 0=>validation success, 1=>validated value
			if(($awaited_type == "boolean" && is_null($input_value)) || ($awaited_type != "boolean" && $input_value === false)) {
				$error = "'%l' should be a %t";
			}
		}

		$error = str_replace("%l", $input_label, $error);
		$error = str_replace("%t", $awaited_type, $error);

		return array("input_label"=>$input_label, "input_value"=>$input_value, "error"=>$error);
	}

	function toPage($untrusted_value, $awaited_type, $on_error_return_value) {

		// conversion to regular charset (avoids attacks by charset modification)
		$input = htmlentities($untrusted_value, ENT_QUOTES, "ISO-8859-1", false);

		switch($awaited_type) {

			case "boolean":
			$input = filter_var($input, FILTER_VALIDATE_BOOLEAN);
			break;

			case "int":
			$input = filter_var($input, FILTER_VALIDATE_INT);
			if($input !== false) { $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT); }
			break;

			case "float":
			$input = filter_var($input, FILTER_VALIDATE_FLOAT);
			if($input !== false) { $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT); }
			break;

			case "string":
			$input = filter_var($input, FILTER_SANITIZE_STRING);
			break;

			case "url":
			$input = filter_var($input, FILTER_VALIDATE_URL);
			if($input !== false) { $input = filter_var($input, FILTER_SANITIZE_URL); }
			break;

			case "email":
			$input = filter_var($input, FILTER_VALIDATE_EMAIL);
			if($input !== false) { $input = filter_var($input, FILTER_SANITIZE_EMAIL); }
		}

		// returns an array with 0=>validation success, 1=>validated value
		if(($awaited_type == "boolean" && is_null($input)) || ($awaited_type != "boolean" && $input === false)) {
			return $on_error_return_value;
		} else {
			return $input;
		}
	}

	function toDb($untrusted_value) {
		return mysql_real_escape_string($untrusted_value);
	}

	function getObjectInfos($object_id, $info) {

		global $database_name;

		switch($info) {

			case "is_managed":
			$sql = "SELECT permission_id FROM rs_data_permissions ";
			$sql .= "WHERE object_id = " . $object_id . " AND permission = 'manage' AND user_id <> 0 LIMIT 1;";
			$temp = db_query($database_name, $sql, "no", "no"); if($temp_ = fetch_array($temp)) { return true; } else { return false; }
			break;

			case "current_user_is_manager":
			$sql = "SELECT permission_id FROM rs_data_permissions ";
			$sql .= "WHERE object_id = " . $object_id . " AND permission = 'manage' AND user_id = " . $_COOKIE["bookings_user_id"] . " LIMIT 1;";
			$temp = db_query($database_name, $sql, "no", "no"); if($temp_ = fetch_array($temp)) { return true; } else { return false; }
			break;

			case "managers_names":
			$sql  = "SELECT rs_data_users.last_name, rs_data_users.first_name FROM rs_data_users ";
			$sql .= "INNER JOIN rs_data_permissions ON rs_data_users.user_id = rs_data_permissions.user_id ";
			$sql .= "WHERE rs_data_permissions.object_id = " . $object_id . " ";
			$sql .= "AND rs_data_permissions.permission = 'manage' ";
			$sql .= "ORDER BY rs_data_users.last_name, rs_data_users.first_name;";
			$temp = db_query($database_name, $sql, "no", "no"); $managers_names = "";

			while($temp_ = fetch_array($temp)) { $managers_names .= unDuplicateName($temp_["first_name"], $temp_["last_name"]) . ", "; }

			if($managers_names != "") { $managers_names = substr($managers_names, 0, -2); }

			return $managers_names;

			break;

			case "managers_emails":
			$sql  = "SELECT email FROM rs_data_users ";
			$sql .= "INNER JOIN rs_data_permissions ON rs_data_users.user_id = rs_data_permissions.user_id ";
			$sql .= "WHERE rs_data_permissions.object_id = " . $object_id . " ";
			$sql .= "AND rs_data_permissions.permission = 'manage' ";
			$sql .= "ORDER BY rs_data_users.last_name, rs_data_users.first_name;";
			$temp = db_query($database_name, $sql, "no", "no"); $managers_emails = "";

			while($temp_ = fetch_array($temp)) { $managers_emails .= $temp_["email"] . ","; }
			if($managers_emails != "") { $managers_emails = substr($managers_emails, 0, -1); }

			return $managers_emails;
		}
	}

	function checkBooking($book_id, $object_id, $booking_start, $booking_end) {

		$error_msg = "";

		if($booking_start == $booking_end) {
			$error_msg = Translate("The booking cannot be recorded with start = end", 1) . ".";
		}

		global $database_name, $time_offset;

		// checks if the new booking does not cover an existant one
		$sql = "SELECT book_id FROM rs_data_bookings ";
		$sql .= "WHERE object_id = " . $_REQUEST["object_id"] . " ";

		// |  ---|---
		$sql .= "AND ((book_start >= '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "' ";
		$sql .= "AND book_start < '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "' ";
		$sql .= "AND book_end >= '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "') ";

		// ---|---  |
		$sql .= "OR (book_start <= '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "' ";
		$sql .= "AND book_end > '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "' ";
		$sql .= "AND book_end <= '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "') ";

		// |-------|
		$sql .= "OR (book_start >= '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "' ";
		$sql .= "AND book_end <= '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "') ";

		// -|-----|-
		$sql .= "OR (book_start < '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "' ";
		$sql .= "AND book_end > '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "')) ";

		$sql .= "AND book_id <> '" . $book_id . "';";

		$temp = db_query($database_name, $sql, "no", "no");

		if(num_rows($temp)) {
			$error_msg = Translate("This booking cannot be recorded as is covers another one", 1) . ".";
		}

		return $error_msg;
	}

	function getAvailability($object_id, $start_date, $duration) { // used for stacking booking

		global $database_name, $date_format;

		$array_duration = explode("|", $duration); // d|h|i
		$duration_stamp = $array_duration[0] * 86400 + $array_duration[1] * 3600 + $array_duration[2] * 60;

		$sql  = "SELECT book_id, book_start, book_end FROM rs_data_bookings ";
		$sql .= "WHERE object_id = " . $object_id . " ";
		$sql .= "AND book_end > '" . $start_date . "' ";
		$sql .= "ORDER BY book_end ASC;";
		$temp = db_query($database_name, $sql, "no", "no");

		$book_start = "";
		$previous_book_end = $start_date;

		if(num_rows($temp)) {

			while($temp_ = fetch_array($temp)) {

				$hole_start = strtotime($previous_book_end);
				$hole_end = strtotime($temp_["book_start"]);

				if($duration_stamp <= ($hole_end - $hole_start)) {

					$book_start = $hole_start;
					break; // exits while loop
				}

				$previous_book_end = $temp_["book_end"];
			}

			$hole_start = strtotime($previous_book_end);
			$hole_end = strtotime($temp_["book_start"]);

			$book_start = $hole_start;

		} else {

			$book_start = date($date_format . " H:i", strtotime($start_date));
		}

		return date($date_format . " H:i", $book_start);
	}

	function Translate($english, $special_chars_to_html) {

		global $database_name;

		if(isset($_COOKIE["bookings_user_id"])) { // user is logged -> uses user language setting
			$language = $_COOKIE["bookings_language"];
		} else { // no user logged -> uses app language setting

			$sql = "SELECT param_value FROM rs_param WHERE param_name = 'language';";
			$temp = db_query($database_name, $sql, "no", "no"); $temp_ = fetch_array($temp);
			$language = $temp_["param_value"];
		}

		$sql = "SELECT " . $language . " FROM rs_param_lang ";
		$sql .= "WHERE english = '" . toDb($english) . "';";
		$translation = db_query($database_name, $sql, "no", "no");

		if($translation_ = fetch_array($translation)) {
			if($translation_[$language] != "" && !is_null($translation_[$language])) {

				if($special_chars_to_html) { return htmlentities(stripslashes($translation_[$language]));
				} else { return stripslashes($translation_[$language]); } // do not convert specials characters to html (usually for javascript alert box)

			} else { return $english . "*"; }

		} else {

			// inserts english if database record is missing. It's a tip to get a list of missing vocabulary while developping the application
			$sql = "INSERT INTO rs_param_lang ( english ) VALUES ( '" . toDb($english) . "' );";
			db_query($database_name, $sql, "yes", "no");

			return $english . "*"; // the star shows translations missing in the database while browsing the app
		}
	}

	function getMonday($year, $week) {
	    $Jan1 = mktime(1,1,1,1,1,$year);
	    $MondayOffset = (11-date('w',$Jan1))%7-3;
	    $desiredMonday = strtotime(($week-1) . ' weeks '.$MondayOffset.' days', $Jan1);
	    return $desiredMonday;
	}

	function getDuration($seconds) { // converts a number of seconds into a days|hours|minutes array.

		$days = floor($seconds / 86400);

		$seconds -= ($days * 86400);
		$hours = floor($seconds / 3600);


		$seconds -= ($hours * 3600);
		$minutes = floor($seconds / 60);

		$seconds -=  ($minutes * 60);

		return array("days"=>$days, "hours"=>$hours, "minutes"=>$minutes, "seconds"=>$seconds);
	}

	function FormatDate($date_a_verifier, $debutfin) {

		// modif semaine sur un seul chiffre
		if(strlen($date_a_verifier) <= 2 || ( strlen($date_a_verifier) >= 5  && strlen($date_a_verifier) <= 7)) { //pour prendre 6 caract�res ex: 1/2005

			// S�lection de l'ann�e
			if(strlen($date_a_verifier) > 2) {
				list($semaine, $annee) = explode("/", $date_a_verifier); // Format semaine/annee (ex: 5/2006 ou 05/2006)
			} else {
				$annee = date("Y", strtotime(date("Y-m-d"))); // Format semaine (ex: 5 ou 05)
			}

			$timestamp = strtotime($annee . "-01-01"); // timestamp du premier janvier de l'ann�e choisie

			while(intval(date("W",$timestamp)) != 1) { $timestamp = strtotime("+1 days", $timestamp); } // Avancer jusqu'� la semaine 1 (ann�e-01-01 peut �tre en semaine 53)

			while(intval(date("W", $timestamp)) != $date_a_verifier) { $timestamp = strtotime("+1 weeks", $timestamp); } // avancer semaine par semaine jusqu'� la semaine sp�cifi�e par l'utilisateur

			switch($debutfin) {

				case "debut": return date("Y-m-d", $timestamp); break; // date du premier jour de la semaine
				case "fin": return date("Y-m-d", strtotime("+6 days", $timestamp)); break; // date du dernier jour de la semaine

				case "debut_annee":
				$ts = strtotime(date("Y", $timestamp) . "-01-01");
				while(intval(date("W",$ts)) != 1) { $ts = strtotime("+1 days", $ts); }
				return date("Y-m-d", $ts); break;

				case "fin_annee":
				$ts = strtotime(date("Y", $timestamp) . "-12-31");
				while(intval(date("W",$ts)) != 52) { $ts = strtotime("-1 days", $ts); }
				return date("Y-m-d", $ts); break;
			}
		}

		//modif ann�e en 31/12/2005 ou 31/12/05 :
		if(strlen($date_a_verifier) == 10 || strlen($date_a_verifier) == 8 ) {
			list($jour, $mois, $annee) = explode("/", $date_a_verifier);
			return $annee . "-" . $mois . "-" . $jour;
		}

	} // Fin de function FormatDate()

	// Sticks the hour to the date
	function DateAndHour($date, $hour) {

		global $time_offset;

		return date("Y-m-d H:i", strtotime($date) + $hour + $time_offset);
	}

	function DateReformat($date) { // changes date to mysql-compliant format using $date_format setting

		global $date_format; $date_item_id = 0;

		for($i=0;$i<=strlen($date_format)-1;$i++) {

			$date_part = substr($date_format, $i, 1);

			if($date_part == "Y" || $date_part == "m" || $date_part == "d") {

				$date_item[$date_item_id] = $date_part; //2
				$date_item_id++;

			} else {
				$separator = substr($date_format, $i, 1);
			}

		} // for

		$date_array = explode($separator, $date);
		$date_item = array_flip($date_item);

		return $date_array[$date_item["Y"]] . "-" .  $date_array[$date_item["m"]] . "-" .  $date_array[$date_item["d"]];

	} // function

	function param_extract($param_name) {

		global $database_name;

		$sql = "SELECT param_value FROM rs_param WHERE param_name = '" . $param_name . "';";
		$temp = db_query($database_name, $sql, "no", "no");

		if($temp_ = fetch_array($temp)) { $result = $temp_["param_value"]; } else { $result = false; }
		return $result;
	}

	function CheckCookie() {  // Resets app to the index page if timeout is reached

		$session_timeout = param_extract("session_timeout");

		if(!isset($_COOKIE["bookings_user_id"])) {

			echo "<html><head>\n";
			echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n";
			echo "<title>Session has expired</title>\n";
			echo "<script type=\"text/javascript\"><!-- \n";
			echo "top.location = \"index.php\";\n";
			echo " --></script>\n";
			echo "</head>\n";
			echo "<body></body>\n";
			echo "</html>\n";

			exit();
		}
	}

	function sqlInjectShield($suspected_text) { // basic protection against sql injections

		$banned_words = array("insert", "select", "update", "delete", "distinct", "having", "truncate", "replace", "handler", "like", "as", "or", "procedure", "limit", "order by", "group by", "asc", "desc");

		if(eregi("[a-zA-Z0-9]+", $suspected_text)) {
			return trim(str_replace($banned_words, "", strtolower($suspected_text)));
		} else {
			return "";
		}
	}

	function insertBooking($action, $book_id, $booker_id, $object_id, $booking_start, $booking_end, $misc_info, $validated) {

		global $app_url, $database_name, $time_offset;

		$misc_info = addslashes($misc_info);

		// extracts booker name from booker id
		$sql = "SELECT first_name, last_name, email ";
		$sql .= "FROM rs_data_users ";
		$sql .= "WHERE user_id = " . $booker_id . ";";
		$temp = db_query($database_name, $sql, "no", "no"); $temp_ = fetch_array($temp);
		$booker_name = $temp_["first_name"] . " " . $temp_["last_name"];
		$booker_email = $temp_["email"];

		// extracts object name from object id
		$sql = "SELECT object_name, email_bookings FROM rs_data_objects WHERE object_id = " . $object_id . ";";
		$temp = db_query($database_name, $sql, "no", "no"); $temp_ = fetch_array($temp);
		$object_name = $temp_["object_name"]; $email_bookings = $temp_["email_bookings"];

		switch($action) {

			case "update":

			$sql = "UPDATE rs_data_bookings SET ";
			$sql .= "book_start = '" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "', ";
			$sql .= "book_end = '" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "', ";
			$sql .= "validated = " . $validated . ", ";
			$sql .= "misc_info = '" . $misc_info . "' ";
			$sql .= "WHERE book_id = " . $_REQUEST["book_id"] . ";";
			db_query($database_name, $sql, "no", "no");

			break;

			case "insert":

			// creates a random code
			$rand_code = rand(0,65535);

			// inserts the new booking associated with the random code
			$sql  = "INSERT INTO rs_data_bookings ( rand_code, object_id, book_date, user_id, book_start, book_end, misc_info, validated ) VALUES ( ";
			$sql .= $rand_code . ", ";
			$sql .= $object_id . ", ";
			$sql .= "'" . date("Y-m-d H:i:s") . "', ";
			$sql .= $booker_id . ", ";
			$sql .= "'" . date("Y-m-d H:i:s", strtotime($booking_start) + $time_offset) . "', ";
			$sql .= "'" . date("Y-m-d H:i:s", strtotime($booking_end) + $time_offset) . "', ";
			$sql .= "'" . $misc_info . "', ";
			$sql .= $validated . " );";

			db_query($database_name, $sql, "no", "no");

			// gets new booking id using the random code
			$sql = "SELECT book_id FROM rs_data_bookings WHERE rand_code = " . $rand_code . ";";
			$new_booking = db_query($database_name, $sql, "no", "no"); $new_booking_ = fetch_array($new_booking);
			$book_id = $new_booking_["book_id"];

			// clears random code
			$sql = "UPDATE rs_data_bookings SET rand_code = '' WHERE rand_code = " . $rand_code . ";";
			db_query($database_name, $sql, "no", "no");

			if(!$validated && getObjectInfos($object_id, "is_managed") && $email_bookings == "yes") { // sends an email for the object's manager to validate the new booking

				$headers  = "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
				$headers .= "From: " . $booker_name . " <" . $booker_email . ">\r\n";

				$message = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
				$message .= "<html>\n";
				$message .= "<head>\n";
				$message .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n";
				$message .= "<title>iframe</title>\n";

				$message .= "<style type=\"text/css\">\n";
				$message .= "a:link {color:black; text-decoration: none; }\n";
				$message .= "a:visited {color:black; text-decoration: none; }\n";
				$message .= "a:hover {color:red; text-decoration: none; }\n";
				$message .= "table { border-collapse: collapse; }\n";
				$message .= "td { padding: 3px; }\n";
				$message .= "</style>\n";

				$message .= "</head>\n";

				$message .= "<body>\n";

				$message .= $booker_name . " " . Translate("has sent the following booking request", 1) . " :\n";
				$message .= "<p>\n";
				$message .= Translate("Object", 1) . " : " . $object_name . "<br>\n";
				$message .= Translate("Start", 1) . " : " . date($date_format . " H:i", strtotime($booking_start)) . "<br>\n";
				$message .= Translate("End", 1) . date($date_format . " H:i", strtotime($booking_end)) . "<p>\n";
				$message .= Translate("This booking has already been recorded to the calendar but needs one of the following action", 1) . " :<p>\n";

				$message .= "<table><tr><td>\n";
				$message .= "<a	href=\"" . $app_url . "actions.php?action_=confirm_booking&book_id=" . $book_id . "&validated=yes\" target=\"action_iframe\">[ " . Translate("Accept", 1) . " ]</A>\n";
				$message .= "</td><td style=\"width:20px\"></td><td>\n";
				$message .= "<a	href=\"" . $app_url . "actions.php?action_=confirm_booking&book_id=" . $book_id . "&validated=no\" target=\"action_iframe\">[ " . Translate("Cancel", 1) . " ]</A>\n";
				$message .= "</td></tr></table>\n";

				$message .= "<iframe frameborder=\"0\" name=\"action_iframe\" id=\"action_iframe\" style=\"border:none; width:500px; height:100px\">\n";
				$message .= "</body>\n";
				$message .= "</html>";

				mail(getObjectInfos($object_id, "managers_emails"), Translate("Booking validation request", 1), $message, $headers);
			}

			return true;
		}
	}
?>
