<?php
session_start();
if(isset($_GET['actie'])){
	if($_GET['actie'] == "uitloggen"){
		session_unset();
		$_SESSION = array();
		unset($_SESSION['whatever']);
		$begin = true;

	}
}

require_once("connect.php");

if(isset($_POST['action'])){
	if($_POST['action'] == 'login'){
		$email = strtolower(mysql_real_escape_string($_POST['email']));
		$bestaat = mysql_result(mysql_query("SELECT count(ww) FROM sint_gebruikers WHERE email = '" . $email . "' "), 0);
		if(!$bestaat){
			$_GET['actie'] = "geenaccount";
		}
		else {
		$query = "SELECT ww FROM sint_gebruikers WHERE email = '" . $email . "' ";
		$ww = mysql_result(mysql_query($query), 0);
		if($ww == md5($_POST['wachtwoord'])){
			$query = "SELECT id,acties,email,naam FROM sint_gebruikers WHERE email = '" . $email . "' ";
			$resultaat = mysql_query($query);
			$_SESSION['id'] = mysql_result($resultaat, 0);
			$_SESSION['acties'] = mysql_result($resultaat, 0, 1);
			$_SESSION['email'] = mysql_result($resultaat, 0, 2);
			$_SESSION['naam'] = mysql_result($resultaat, 0, 3);
			
			$opties = explode(",", $_SESSION['acties']);
			$zoekstr = "acties LIKE '%" . join($opties, "%' OR acties LIKE '%") . "%'";			
			$query = mysql_query("SELECT id,naam FROM sint_gebruikers WHERE " . $zoekstr);
			while($gebr = mysql_fetch_row($query)){
				$_SESSION['gebruikers'][$gebr[0]] = $gebr[1];
			}
			$opties = "'" . join($opties, "' OR id ='") . "'";
			$_SESSION['acties'] = $opties;
			$_GET['actie'] = "prof";
		}
		}
	}
	elseif($_POST['action'] == "passw"){
		$_GET['actie'] = "wachtwijzig";
	  if(isset($_SESSION['id'])){
		if($_POST['wachtwoord'] == $_POST['wachtwoord2']){
			if(strlen($_POST['wachtwoord']) > 4){
				$query = "UPDATE sint_gebruikers SET ww = '" . md5($_POST['wachtwoord']) . "' WHERE id = " . $_SESSION['id'] . " LIMIT 1";
				mysql_query($query);
				print mysql_error();
				if (mysql_error()){
					$ietsmis = true; 
				}
				else $gelukt = true;
			}
			else $tekort = true;
			
		}
		else $verschillend = true;
	  }
	else $nietin = true;
		
	}
	elseif($_POST['action'] == "wensen"){
		if(isset($_SESSION['id'])){
			$acties = mysql_result(mysql_query("SELECT acties FROM sint_gebruikers WHERE id = " . $_SESSION['id']), 0);
			$wens = mysql_real_escape_string($_POST['wens']);
			$query = "INSERT INTO sint_wensen (wenser, wens, acties) VALUES (" . $_SESSION['id'] . ", '" . $wens . "', '" . $acties . "')";
			mysql_query($query);
			$_GET['actie'] = "prof";
			
		}
	}
	elseif($_POST['action'] == "addact"){
		if(isset($_SESSION['id'])){
			$naam = mysql_real_escape_string($_POST['naam']);
			$regels = mysql_real_escape_string($_POST['regels']);
			$query = "INSERT INTO sint_acties (naam, eigenaar,regels) VALUES ('" . $naam . "', " . $_SESSION['id'] . ", '" . $regels . "')";
			mysql_query($query);
			$aantal = $_POST['aantal'];
			if($aantal == (int)$aantal){
				if ($aantal > 0){
					$_GET['actie'] = "voegtoe";
				}
				else {
					$_GET['actie'] = "niegenoeg";
				}
			}
			
		}
	}
	elseif($_POST['action'] == "vulactie"){
		$outpstring = '';
		$actienaam = mysql_real_escape_string($_POST['actienaam']);
		$id = mysql_result(mysql_query("SELECT id FROM sint_acties WHERE naam = '" . $actienaam . "' limit 1"), 0);
		$mensen = $_POST['namen'];
		$uniek = true;
		while($uniek){
			$uniek = false;
			shuffle($mensen);
			for($i = 0; $i < $len = count($mensen); $i++){
				if($mensen[$i] == $_POST['namen'][$i]){
					$uniek = true;
				}
			}
		}
		foreach($_POST['email'] as $i => $email){
			if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email)) { 
				$outpstring .= $email . ' is geen geldig e-mail adres en is niet toegevoegd! <br />';
			} else {
				$naam = strtolower(mysql_real_escape_string($_POST['namen'][$i]));
				$query = "SELECT count(id) FROM sint_gebruikers WHERE email = '" . $email . "'";
				$bestaat = mysql_result(mysql_query($query), 0);
				if($bestaat){
					mysql_query("UPDATE sint_gebruikers SET acties = CONCAT(acties, '," . $id . "') WHERE email = '" . $email . "'");
					$outpstring .= $email . " had al een account, en is toegevoegd. <br />";
					$gebruiker = mysql_fetch_row(mysql_query("SELECT id,acties FROM sint_gebruikers WHERE email = '" . $email . "'"));
					mysql_query("UPDATE sint_wensen SET acties = '" . $gebruiker['1'] . "' WHERE wenser = " . $gebruiker['0']);
					echo $naam, " heeft ", $mensen[$i], "<br />";
				}
				else {
					$outpstring .= $email. " heeft nog geen account. Er is een e-mail verzonden met een activatie verzoek. <br />";
					$wachtwoord = $naam . rand(0,100);
					$wachtwoordmd = md5($wachtwoord);
					mysql_query("INSERT INTO sint_gebruikers (naam,email,ww,acties) VALUES ('" . $naam . "', '" .strtolower($email) . "', '" . $wachtwoordmd . "', '" . $id . "')");
					
					if(mail($email, "Surprise! " . $actienaam, "Hallo " . $naam . ", 
Iemand heeft je toegevoegd aan de surprise site sint.egdk.nl.
Neem snel een kijkje!

Je gebruikersnaam is je email adres (" . $email . ").
Je (tijdelijke) wachtwoord is " . $wachtwoord . " PAS OP! Je wachtwoord is hoofdletter gevoelig.
Dit is de naam zoals de uitnodiger hem heeft ingevuld. Je kan je naam wijzigen op je profiel pagina.

Je moet iets maken voor: " . $mensen[$i] . "

Als je vragen hebt, kun je deze altijd naar sint.egdk.nl sturen.

Met pepernotige groetjes,
Cyber piet.", 'From: Sint op gnur.nl <sint.egdk.nl>' . "\r\n")){
	echo "t is verzonden";
	
}
				}
				
			}
		}
		$_GET['actie'] = "uitgenodigt";
	}
}

		if($_GET['actie'] == "echtuit"){
			if(isset($_GET['id']) && isset($_SESSION['id'])){
				$query = mysql_query("SELECT acties FROM sint_gebruikers WHERE id = " . $_SESSION['id']);
				$acties = mysql_result($query, 0);
				$acties = explode(",", $acties);
				if(count($acties) > 1){
					$newacties = $acties[0];
					if ($newacties == $_GET['id']) $newacties = "";
					foreach($acties as $a){
						if ($a != $_GET['id']){
							if(strlen($newacties)){
								if($newacties != $a) $newacties = $newacties . "," . $a;
							}
							else $newacties = $a;
						}
					}
				}
				else {
					$newacties = "";
				}
				$query = mysql_query("UPDATE sint_gebruikers SET acties = '" . $newacties . "' WHERE id =" . $_SESSION['id']);
				$query = mysql_query("UPDATE sint_wensen SET acties = '" . $newacties . "' WHERE wenser =" . $_SESSION['id']);
				session_unset();
				$_SESSION = array();
				unset($_SESSION['whatever']);
				$begin = true;
			
			}
		}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
  <title>De ultieme sint-site</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="STYLESHEET" type="text/css" href="sint.css" />
</head>

<body>
<div id="holder">
	<div id="article">
		<?php 
		  if(!isset($_GET['actie']) || $begin) { ?>
		<h1>Welkom bij de allerhandigste sint site.</h1><br />
		<p>
			Deze site helpt je met het bijhouden van al je surprises en kado-wensen. <br />
			Op het moment moet kan je je nog niet zelf registreren. Kijk voor meer informatie bij het kopje nieuwe sint actie.
		
		</p>
		
		<?php } 
		elseif($_GET['actie'] == "help"){
				?>
			<h1>HELP! Wat kan ik nou met deze site?</h1>
			<p>De bedoeling van sint.egdk.nl is om het iedereen iets makkelijker te maken. <br />
				Met sint.egdk.nl kan je verschillende surprise bijeenkomsten combineren tot een makkelijk overzicht. <br />
				Je verlanglijstje is altijd up-to-date voor al je surprises. Iedereen kan zien wat jij wil hebben, en als iets al gekocht is 
				wordt dit aangegeven op de verlanglijstjes. Op die manier kan je iedereen hetzelfde verlanglijstje laten zien, zonder dat je bang
				hoeft te zijn dat je iets dubbel krijgt. </p>
				
			<p>Dit is allemaal mogelijk doordat je surprise-genoten kunnen aangeven wat zij al hebben gekocht en kunnen zien wat al is gekocht.
				Maar wees gerust, niemand kan van zichzelf zien wat al voor hun is gekocht. Ook is het mogelijk om je aankopen bij te houden, zodat 
				je kan zien wat je voor iedereen hebt gekocht. </p>
				
				<?php
			}
		elseif($_GET['actie'] == "geenaccount"){
				?>
			<h1>HELP! Ik wil een account!</h1>
			<p>Op het moment werkt sint.egdk.nl alleen nog met uitnodigingen. Als je een account wilt hebben zul je naar sint.egdk.nl moeten mailen 
				met een mooi sint-rijm. Als het een beetje rijmt heb je grote kans dat je een account krijgt. <br />
				
			<p>Heb je al wel een account? <br />
				Let op dat je inlogt met het juiste e-mail adres! Ook je wachtwoord moet kloppen. Als je je wachtwoord bent vergeten kun je een nieuw wachtwoord
				aanvragen door een mailtje naar sint.egdk.nl te sturen. </p>
				
				<?php
			}
		elseif($_GET['actie'] == "mobiel"){
				$wensen = mysql_query("SELECT wenser,wens,gekocht,id FROM sint_wensen WHERE acties LIKE '%19%' AND wenser != 29 AND gekocht % 2 = 0 ORDER BY wenser");
				$gebruiker = -1;
				$gebruikers = array(15 => "Arjan", 16 => "Ingrid", 17 => "Papa", 18 => "Mama", 30 => "Tamara");
				echo "<ul>";
				while($wens = mysql_fetch_row($wensen)){
					if ($wens[0] != $gebruiker){
						echo "</ul><br /><strong>" . $gebruikers[$wens[0]] . " wil graag: </strong><br /><ul>";
						$gebruiker = $wens[0];
					}
					echo "<li><a href='index.php?actie=kopen&wens=", $wens[3], "&id=" . $id . "'>", stripslashes($wens[1]), "</a> ", ($wens[2] % 2 == 1) ? "(gekocht)":"", "</li>";
				}
				echo "</ul>";
			}
			
		elseif($_GET['actie'] == "inloggen"){
			if(!isset($_SESSION['id'])){
				?>
			<h1>Inloggen</h1>
			<p>Om in te loggen moet je al lid zijn van een sint-actie. <br />
			   Inloggen doe je voor alle surprises waaraan je meedoet tegelijk. Als je ingelogt bent krijg je een overzicht van surprise acties.</p>
			
			<p><form action='index.php' method='POST'>
         		<input type='hidden' name='action' value='login'>
         			<strong>E-mail:</strong><br />
         		<input type='input' name='email' /><br />
         			<strong>Wachtwoord:</strong><br />
         		<input type='password' name='wachtwoord' /><br />
         			<input type='submit' value='inloggen' />
         </form>
				</p>
				
				<?php
				}
				else {
					?>
				<h1>Uitloggen</h1>
				<p>Al je sint aankopen weer verwerkt? <br />
				   Klik dan <a href='index.php?actie=uitloggen'>hier</a> om uit te loggen </p>
					
					
					<?php
				}
			}
		elseif($_GET['actie'] == "kopen"){
			if(isset($_GET['wens']) && isset($_SESSION['id'])){
				$query = mysql_query("SELECT koper,gekocht FROM sint_wensen WHERE id = " . $_GET['wens'] . " limit 1");
				$koper = mysql_result($query, 0);
				$gekocht = mysql_result($query, 0, 1);
				if($koper == 0){
					$query = "UPDATE sint_wensen SET gekocht = gekocht + 1, koper = " . $_SESSION['id'] . " WHERE id = " . $_GET['wens'] . " limit 1";
					mysql_query($query);
				}
				elseif($koper == $_SESSION['id'] && $gekocht % 2 == 1){
					$query = "UPDATE sint_wensen SET gekocht = gekocht + 1, koper = 0 WHERE id = " . $_GET['wens'] . " limit 1";
					mysql_query($query);
				}
				else {
					$query = "UPDATE sint_wensen SET gekocht = gekocht + 1 WHERE id = " . $_GET['wens'] . " limit 1";
					mysql_query($query);
				}
				
				
			}
			$lijst = true;
		}
		if($_GET['actie'] == "lijst" || $lijst){
			if(isset($_GET['id']) && isset($_SESSION['id'])){
				$id = mysql_real_escape_string($_GET['id']);
				$naam = mysql_result(mysql_query("SELECT naam FROM sint_acties WHERE id = " . $id . " limit 1"), 0);
				echo "<h1>", $naam, "</h1>";
				$wensen = mysql_query("SELECT wenser,wens,gekocht,id FROM sint_wensen WHERE acties LIKE '%" . $id . "%' AND wenser != " . $_SESSION['id'] . " ORDER BY wenser");
				$gebruiker = -1;
				echo "<ul>";
				while($wens = mysql_fetch_row($wensen)){
					if ($wens[0] != $gebruiker){
						echo "</ul><br /><strong>" . $_SESSION['gebruikers'][$wens[0]] . " wil graag: </strong><br /><ul>";
						$gebruiker = $wens[0];
					}
					echo "<li><a href='index.php?actie=kopen&wens=", $wens[3], "&id=" . $id . "'>", stripslashes($wens[1]), "</a> ", ($wens[2] % 2 == 1) ? "(gekocht)":"", "</li>";
				}
				echo "</ul>";
				
				$regels = mysql_result(mysql_query("SELECT regels FROM sint_acties WHERE id = " . $id . " limit 1"), 0);
				echo "<p><br />De regels: <div id='regels'>", stripslashes(nl2br($regels)) . "</div></p>";
			}
			else echo "<p>helaas is je sessie verlopen, je zult opnieuw moeten inloggen</p>";
		}
		elseif($_GET['actie'] == "prof"){
			if(isset($_SESSION['id'])){
				
				echo '<h2> Profiel van ', $_SESSION['naam'], ' (', $_SESSION['email'], ')</h2>';
				
				echo "<p>Je wensen tot nu toe: </p><ul>";
				$query = mysql_query("SELECT wens FROM sint_wensen WHERE wenser = " . $_SESSION['id']);
				while($wens = mysql_fetch_row($query)){
					echo "<li>", $wens[0], "</li>";
				}
				echo "</ul></p>";
				?>
				<p>Cadeaus toevoegen! <br />
					
				<form action='index.php' method='POST'>
         		<input type='hidden' name='action' value='wensen'>
         			<strong>Je wens:</strong><br />
         		<input type='input' name='wens' /><br />
         			<input type='submit' value='Wens Toevoegen' />
				</form><br /><br />
					</p>
				
				
				<p>Wachtwoord wijzigen? <br />
					Type je wachtwoord hieronder 2 keer hetzelfde en dit zal je nieuwe wachtwoord worden. 
					<form action='index.php' method='POST'>
         		<input type='hidden' name='action' value='passw'>
         			<strong>Wachtwoord (2x):</strong><br />
         		<input type='password' name='wachtwoord' /><input type='password' name='wachtwoord2' /><br />
         			<input type='submit' value='Wijzigen' />
				</form>
				<br /><br /><br />
				</p>
				<p>
				Op het moment doe je mee aan deze surprise(s) :<br />
				<?php
				$query = mysql_query("SELECT naam,id FROM sint_acties WHERE id = " . $_SESSION['acties'] ."");
				while($actie = mysql_fetch_row($query)){
					echo "<a href='index.php?actie=lijst&id=" . $actie[1] . "'>" . $actie[0] . "</a> (<a href='index.php?actie=uitschr&id=" . $actie[1] . "'>X</a>)<br />";
				}
				echo "<br />Als er surprises bij staan waar jij niks van weet of niet langer meedoet, kan je ze verwijderen door op (x) te klikken. <br />
				Als je op de naam zelf klikt kom je bij de wensen pagina van die surprise.</p>";
			}
		}
		elseif($_GET['actie'] == "uitschr"){
			if(isset($_GET['id']) && isset($_SESSION['id'])){
				echo "<h2>Uitschrijven van een surprise</h2>";
				
				$query = mysql_query("SELECT naam FROM sint_acties WHERE id = " . $_GET['id']);
				$naam = mysql_result($query, 0);
				echo "<p>Weet je echt zeker dat je niet bij deze surprise hoort? <br /><br /><strong>", $naam;
				echo "</strong><br /><br />Zoja, klik dan op dan <a href='index.php?actie=echtuit&id=" . $_GET['id'] . "'>uitschrijven</a> om je hier van uit te schrijven. <br />";
				echo "Pas op! Dit proces is alleen om te keren door de webmaster zelf! </p>";
				
			}
		}
		elseif($_GET['actie'] == "wachtwijzig"){
			echo "<h1>Wachtwoord wijzigen</h1>";
			echo "<p>";
			if($ietsmis){
				echo "Alles was goed ingevuld, toch is er iets misgegaan. Probeer het later nog eens.";
			}
			elseif($gelukt){
				echo "Je wachtwoord is nu gewijzigd, onthoud je nieuwe wachtwoord goed!";
			}
			elseif($tekort){
				echo "Het wachtwoord dat je hebt gekozen is te kort, het moet minstens 5 karakters hebben.";
			}
			elseif($verschillend){
				echo "Je hebt 2 verschillende wachtwoorden ingetypt. Dit was vast niet de bedoeling!";
			}
			elseif($nietin){
				echo "Je bent niet meer ingelogt! Je sessie is verlopen, om je wachtwoord te kunnen wijzigen moet je eerst weer inloggen.";
			}
		}
		elseif($_GET['actie'] == "aanmelden"){
			if (isset($_SESSION['id'])){
			echo "<h1>Nieuwe surprise toevoegen</h1><p>Als je deze actie aanmaakt wordt je automatisch eigenaar van deze surprise! <br />";
			echo "Als eigenaar kan je mensen toevoegen / verwijderen en de naam van de surprise wijzigen. <br />";
			echo "Eerst een paar vragen over je nieuwe surprise, hierna kun je mensen uitnodigen: <br />";
			?>
			
			
			<form action='index.php' method='POST'>
         		<input type='hidden' name='action' value='addact' />
			Naam van de surprise (bijv. Familie Jansen): <br />
         		<input type='input' name='naam' /><br />
			Aantal mensen dat meedoet (dat je zeker weet, extra mensen toevoegen kan altijd nog):<br />
         		<input type='input' name='aantal' /><br />
			Regels voor de surprise (Budget, voor wie en wellicht de datum):<br />
			<textarea cols="80" rows="8" name="regels"></textarea>
         		<input type='submit' value='Toevoegen' />
			</form>
			</p>
			<?php
			}
			else {
				echo "<h1>Nieuwe surprise toevoegen</h1><p>Als je nog niet ingelogd bent is het niet mogelijk om een nieuwe surprise toe te voegen. <br />";
				echo "Sterker nog, om in te kunnen loggen moet je uitgenodigd worden door iemand anders. Zonder uitnodiging kan je niet beginnen. <br />";
				echo "Als je een uitnodiging wil moet je iemand kennen met toegang tot de site, of je moet een mooi rijmpje naar sint.egdk.nl sturen";
				echo " en dan krijg je wellicht een uitnodiging. Succes.</p>";
			}
		}
		elseif($_GET['actie'] == "voegtoe"){
			if (isset($_SESSION['id'])){
				echo "<h1>" . $naam . "</h1>";
				echo "<p>Nu kun je de namen en e-mailadressen van de mede kandidaten toevoegen.<br />";
				echo "<form method='POST'> <input type='hidden' name='action' value='vulactie' />";
				echo "<input type='hidden' name='actienaam' value='" . $naam . "' />";
				
				for ($i = 0; $i < $aantal; $i++){
					?>
					<br />Naam:<br />
					<input type='input' name ='namen[]' /><br />
					E-mail:<br />
					<input type='input' name='email[]' /><br />
					<?php
				}
				echo "<input type='submit' value='Uitnodigen' /></form></p>";
				
			}
		}
		elseif($_GET['actie'] == "printen"){
            $id = mysql_real_escape_string($_GET['id']);
            $naam = mysql_result(mysql_query("SELECT naam FROM sint_acties WHERE id = " . $id . " limit 1"), 0);
            echo "<h1>", $naam, "</h1>";
            $wensen = mysql_query("SELECT wenser,wens,gekocht,id FROM sint_wensen WHERE acties LIKE '%" . $id . "%' AND wenser != " . $_SESSION['id'] . " ORDER BY wenser");
            $gebruiker = -1;
            echo "<ul>";
            while($wens = mysql_fetch_row($wensen)){
                if ($wens[0] != $gebruiker){
                    echo "</ul><br /><strong>" . $_SESSION['gebruikers'][$wens[0]] . " wil graag: </strong><br /><ul>";
                    $gebruiker = $wens[0];
                }
                if ($wens[2] % 2 == 0) {
                    echo "<li>", stripslashes($wens[1]), "</li>";
                }
            }
            echo "</ul>";
            die();
        }
		elseif($_GET['actie'] == "uitgenodigt"){
			echo '<h1>Nieuwe surprise toegevoegd!</h1>';
			echo '<p>Als je jezelf hebt toegevoegd aan deze surprise moet je opnieuw inloggen om de nieuwe actie te kunnen zien. <br /><br />';
			echo $outpstring, '</p>';
			
		}
		 ?>
	</div>
	
	<div id="other">
		<p> <br /> <br /> <br />
		<a href='index.php?actie=inloggen'>in/uitloggen</a><br />
		<a href='index.php?actie=aanmelden'>nieuwe surprise actie</a><br />
		<a href='index.php?actie=help'>HELP!</a>
		
		<?php
			if(isset($_SESSION['id'])){
				echo "<p><a href='index.php?actie=prof'>Profiel van " , $_SESSION['naam'], "</a>";
				echo "<br /><br />Surprises: <br />";
				
				$query = mysql_query("SELECT naam,id FROM sint_acties WHERE id = " . $_SESSION['acties'] ."");
				while($actie = mysql_fetch_row($query)){
					echo "<a href='index.php?actie=lijst&id=" . $actie[1] . "'>" . $actie[0] . "</a> (<a href='index.php?actie=printen&id=" . $actie[1] . "'>printen</a>)<br />";
				}
			}
		
		
		?>
		</p>
	</div>


</div>
</body>
</html>
