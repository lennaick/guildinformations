<?php

function searchItem($searchText, $guildId, $grade) {
	$allItems = getItems($guildId, $grade);
	$searchResult = array();
	$errors = array();
	foreach($allItems as $key => $value) {
		$found = array();
		foreach($value['items'] as $item) {
			if(isset($item['error'])) {
				if(!isset($errors[$allItems[$key]['guild']['id']])) {
					array_push($found, array('error' => $item));
					$errors[$allItems[$key]['guild']['id']] = true;
				}
			}
			else {
				$pos = stripos($item['name'], $searchText);
				if($pos!==false) {
					array_push($found, $item);
				}
			}
		}
		if(!empty($found)) {
			array_push($searchResult, array('guild' => $allItems[$key]['guild'], 'items' => $found));
		}
	}
	return $searchResult;
}

function getItems($guildId, $grade) {
	$guildResource = new \App\Resource\GuildResource();
	$mainGuild = $guildResource->get($guildId);
	$mainItems = getGuildItems($mainGuild['apiKey']);
	$stuff = array();
	array_push($stuff, array('guild' => $mainGuild, 'items' => $mainItems));
	if($grade=="Leader" || $grade=="HighOfficer") {
		$guilds = $guildResource->getEntityManager()->getRepository('\App\Entity\Guild')->getRelatedGuilds($guildId);
		foreach($guilds as $guild) {
			$guildItems = getGuildItems($guild['apiKey']);
			array_push($stuff, array('guild' => $guild, 'items' => $guildItems));
		}
	}
	return $stuff;
}

function getGuildItems($guildKey) {
	$xml = ryzom_guild_api($guildKey);
	if(isset($xml[$guildKey])) {
		$infos = $xml[$guildKey];
		if($infos === false || isset($infos->error)) {
			$error = array('error' => true, 'message' => "Guild Key Error", 'code' => -2);
			if(isset($infos->error)) {
				$error['code'] = (int) $infos->error['code'];
				$error['message'] = (string) $infos->error;
			}
			return $error;
		}
		$items = array();
		foreach($infos->room->item as $item) {
			$url = ryzom_item_icon_url((string) $item->sheet, (int) $item->craftparameters->color, (int) $item->quality, (int) $item->stack);
			$stack = (int) $item->stack;
			$name = ryzom_translate((string) $item->sheet, 'fr', 0);
			$quality = (int) $item->quality;
			$details = "";
			foreach((array) $item->craftparameters as $nom => $detail) {
				$details .= " - ".$nom." : ".$detail;
			}
			array_push($items, array('iconUrl' => $url, 'name' => $name, 'quality' => $quality, 'stack'=> $stack, 'details' => $details));
		}
		return $items;
	}
	else {
		$error = array('error' => true, 'message' => "Character Key Error", 'code' => -2);
		if(isset($infos->error)) {
			$error['code'] = (int) $xml->error['code'];
			$error['message'] = (string) $xml->error;
		}
		return $error;
	}
}

function sortByType($a, $b) {
	$value = strcmp($a['name'], $b['name']);
	if($value==0) {
		if($a['quality']>$b['quality']) {
			$value = 1;
		}
		else if($a['quality']<$b['quality']) {
			$value = -1;
		}
		else {
			if($a['stack']>$b['stack']) {
				$value = 1;
			}
			else if($a['stack']<$b['stack']) {
				$value = -1;
			}
			else {
				$value = 0;
			}
		}
	}
	return $value;
}

function sortByQuality($a, $b) {
	if($a['quality']>$b['quality']) {
		return 1;
	}
	else if($a['quality']<$b['quality']) {
		return -1;
	}
	else {
		$value = strcmp($a['name'], $b['name']);
		if($value==0) {
			if($a['stack']>$b['stack']) {
				$value = 1;
			}
			else if($a['stack']<$b['stack']) {
				$value = -1;
			}
			else {
				$value = 0;
			}
		}
		return $value;
	}
}

function getFightLevels($apiKey, $branch) {
	$allLevels = getHominMaxLevels($apiKey);
	if(isset($allLevels['error'])) {
		return $allLevels;
	}
	$levels = array(
		'mains' => -1,
		'dague' => -1,
		'masse1' => -1,
		'baton1' => -1,
		'lance1' => -1,
		'hache1' => -1,
		'epee1' => -1,
		'epee2' => -1,
		'hache2' => -1,
		'pique2' => -1,
		'masse2' => -1,
		'handgun' => -1,
		'shotgun' => -1,
		'submachinegun' => -1,
		'lg' => -1
	);
	foreach($allLevels as $code => $level) {
		switch($branch) {
			case 0: // corps à corps
				if(stripos($code, "sfmcad")!==false) {
					$levels['dague'] = $level;
				}
				else if(stripos($code, "sfmcah")!==false) {
					$levels['mains'] = $level;
				}
				else {
					if($code == "sfmca" || $code == "sfmc" || $code == "sfm" || $code == "sf") {
						$levels['dague'] = $level;
						$levels['mains'] = $level;
					}
				}
				break;
			case 1: // arme une main
				if(stripos($code, "sfm1bm")!==false) {
					$levels['masse1'] = $level;
				}
				else if(stripos($code, "sfm1bs")!==false) {
					$levels['baton1'] = $level;
				}
				else if(stripos($code, "sfm1ps")!==false) {
					$levels['lance1'] = $level;
				}
				else if(stripos($code, "sfm1sa")!==false) {
					$levels['hache1'] = $level;
				}
				else if(stripos($code, "sfm1ss")!==false) {
					$levels['epee1'] = $level;
				}
				else {
					if($code == "sfm1b") {
						$levels['masse1'] = $level;
						$levels['baton1'] = $level;
					}
					else if($code == "sfm1p") {
						$levels['lance1'] = $level;
					}
					else if($code == "sfm1s") {
						$levels['hache1'] = $level;
						$levels['epee1'] = $level;
					}
					else {
						if($code == "sfm1" || $code == "sfm" || $code == "sf") {
							$levels['masse1'] = $level;
							$levels['baton1'] = $level;
							$levels['lance1'] = $level;
							$levels['hache1'] = $level;
							$levels['epee1'] = $level;
						}
					}
				}
				break;
			case 2: // arme deux mains
				if(stripos($code, "sfm2b")!==false) {
					$levels['masse2'] = $level;
				}
				else if(stripos($code, "sfm2p")!==false) {
					$levels['pique2'] = $level;
				}
				else if(stripos($code, "sfm2sa")!==false) {
					$levels['hache2'] = $level;
				}
				else if(stripos($code, "sfm2ss")!==false) {
					$levels['epee2'] = $level;
				}
				else {
					if($code == "sfm2s") {
						$levels['hache2'] = $level;
						$levels['epee2'] = $level;
					}
					else {
						if($code == "sfm2" || $code == "sfm" || $code == "sf") {
							$levels['epee2'] = $level;
							$levels['hache2'] = $level;
							$levels['pique2'] = $level;
							$levels['masse2'] = $level;
						}
					}
				}
				break;
			default: // arme à distance
				if(stripos($code, "sfr1")!==false) {
					$levels['handgun'] = $level;
				}
				else if(stripos($code, "sfr2aa")!==false) {
					$levels['submachinegun'] = $level;
				}
				else if(stripos($code, "sfr2al")!==false) {
					$levels['lg'] = $level;
				}
				else if(stripos($code, "sfr2ar")!==false) {
					$levels['shotgun'] = $level;
				}
				else {
					if($code == "sfr2a" || $code == "sfr2") {
						$levels['submachinegun'] = $level;
						$levels['lg'] = $level;
						$levels['shotgun'] = $level;
					}
					else {
						if($code == "sfr" || $code == "sf") {
							$levels['submachinegun'] = $level;
							$levels['lg'] = $level;
							$levels['handgun'] = $level;
							$levels['shotgun'] = $level;
						}
					}
				}
				break;
		}
	}
	return $levels;
}

function getCraftLevels($apiKey, $branch) {
	$allLevels = getHominMaxLevels($apiKey);
	if(isset($allLevels['error'])) {
		return $allLevels;
	}
	$levels = array(
		'bijouCheville' => -1,
		'bijouPoignet' => -1,
		'bijouTete' => -1,
		'bijouOreille' => -1,
		'bijouCou' => -1,
		'bijouDoigt' => -1,
		'armureBBouclier' => -1,
		'armureBRondache' => -1,
		'armureHPied' => -1,
		'armureHMain' => -1,
		'armureHTete' => -1,
		'armureHJambe' => -1,
		'armureHBras' => -1,
		'armureHVentre' => -1,
		'armureMPied' => -1,
		'armureMMain' => -1,
		'armureMJambe' => -1,
		'armureMBras' => -1,
		'armureMVentre' => -1,
		'armureLPied' => -1,
		'armureLMain' => -1,
		'armureLJambe' => -1,
		'armureLBras' => -1,
		'armureLVentre' => -1,
		'arme1Hache' => -1,
		'arme1Dague' => -1,
		'arme1Massue' => -1,
		'arme1Lance' => -1,
		'arme1Epee' => -1,
		'arme1Baton' => -1,
		'arme2Hache' => -1,
		'arme2Massue' => -1,
		'arme2Pique' => -1,
		'arme2Epee' => -1,
		'arme2Ampli' => -1,
		'armeTirHandgun' => -1,
		'armeTirSubmachinegun' => -1,
		'armeTirLg' => -1,
		'armeTirShotgun' => -1
	);
	foreach($allLevels as $code => $level) {
		switch($branch) {
			case 0: // armures légères
				if(stripos($code, "scalb")!==false) {
					$levels['armureLPied'] = $level;
				}
				else if(stripos($code, "scalg")!==false) {
					$levels['armureLMain'] = $level;
				}
				else if(stripos($code, "scalp")!==false) {
					$levels['armureLJambe'] = $level;
				}
				else if(stripos($code, "scals")!==false) {
					$levels['armureLBras'] = $level;
				}
				else if(stripos($code, "scalv")!==false) {
					$levels['armureLVentre'] = $level;
				}
				else {
					if($code == "scal" || $code == "sca" || $code == "sc") {
						$levels['armureLPied'] = $level;
						$levels['armureLMain'] = $level;
						$levels['armureLJambe'] = $level;
						$levels['armureLBras'] = $level;
						$levels['armureLVentre'] = $level;
					}
				}
				break;
			case 1: // armures moyennes
				if(stripos($code, "scamb")!==false) {
					$levels['armureMPied'] = $level;
				}
				else if(stripos($code, "scamg")!==false) {
					$levels['armureMMain'] = $level;
				}
				else if(stripos($code, "scamp")!==false) {
					$levels['armureMJambe'] = $level;
				}
				else if(stripos($code, "scams")!==false) {
					$levels['armureMBras'] = $level;
				}
				else if(stripos($code, "scamv")!==false) {
					$levels['armureMVentre'] = $level;
				}
				else {
					if($code == "scam" || $code == "sca" || $code == "sc") {
						$levels['armureMPied'] = $level;
						$levels['armureMMain'] = $level;
						$levels['armureMJambe'] = $level;
						$levels['armureMBras'] = $level;
						$levels['armureMVentre'] = $level;
					}
				}
				break;
			case 2: // armures lourdes
				if(stripos($code, "scahb")!==false) {
					$levels['armureHPied'] = $level;
				}
				else if(stripos($code, "scahg")!==false) {
					$levels['armureHMain'] = $level;
				}
				else if(stripos($code, "scahh")!==false) {
					$levels['armureHTete'] = $level;
				}
				else if(stripos($code, "scahp")!==false) {
					$levels['armureHJambe'] = $level;
				}
				else if(stripos($code, "scahs")!==false) {
					$levels['armureHBras'] = $level;
				}
				else if(stripos($code, "scahv")!==false) {
					$levels['armureHVentre'] = $level;
				}
				else {
					if($code == "scah" || $code == "sca" || $code == "sc") {
						$levels['armureHPied'] = $level;
						$levels['armureHMain'] = $level;
						$levels['armureHTete'] = $level;
						$levels['armureHJambe'] = $level;
						$levels['armureHBras'] = $level;
						$levels['armureHVentre'] = $level;
					}
				}
				break;
			case 3: // bouclier
				if(stripos($code, "scasb")!==false) {
					$levels['armureBRondache'] = $level;
				}
				else if(stripos($code, "scass")!==false) {
					$levels['armureBBouclier'] = $level;
				}
				else {
					if($code == "scas" || $code == "sca" || $code == "sc") {
						$levels['armureBRondache'] = $level;
						$levels['armureBBouclier'] = $level;
					}
				}
				break;
			case 4: // bijoux
				if(stripos($code, "scja")!==false) {
					$levels['bijouCheville'] = $level;
				}
				else if(stripos($code, "scjb")!==false) {
					$levels['bijouPoignet'] = $level;
				}
				else if(stripos($code, "scjd")!==false) {
					$levels['bijouTete'] = $level;
				}
				else if(stripos($code, "scje")!==false) {
					$levels['bijouOreille'] = $level;
				}
				else if(stripos($code, "scjp")!==false) {
					$levels['bijouCou'] = $level;
				}
				else if(stripos($code, "scjr")!==false) {
					$levels['bijouDoigt'] = $level;
				}
				else {
					if($code == "scj" || $code =="sc") {
						$levels['bijouCheville'] = $level;
						$levels['bijouPoignet'] = $level;
						$levels['bijouTete'] = $level;
						$levels['bijouOreille'] = $level;
						$levels['bijouCou'] = $level;
						$levels['bijouDoigt'] = $level;
					}
				}
				break;
			case 5: // armes à une main
				if(stripos($code, "scm1a")!==false) {
					$levels['arme1Hache'] = $level;
				}
				else if(stripos($code, "scm1d")!==false) {
					$levels['arme1Dague'] = $level;
				}
				else if(stripos($code, "scm1m")!==false) {
					$levels['arme1Massue'] = $level;
				}
				else if(stripos($code, "scm1p")!==false) {
					$levels['arme1Lance'] = $level;
				}
				else if(stripos($code, "scm1s")!==false) {
					$levels['arme1Epee'] = $level;
				}
				else if(stripos($code, "scm1t")!==false) {
					$levels['arme1Baton'] = $level;
				}
				else {
					if($code == "scm1" || $code == "scm" || $code =="sc") {
						$levels['arme1Hache'] = $level;
						$levels['arme1Dague'] = $level;
						$levels['arme1Massue'] = $level;
						$levels['arme1Lance'] = $level;
						$levels['arme1Epee'] = $level;
						$levels['arme1Baton'] = $level;
					}
				}
				break;
			case 6: // armes à deux mains + amplificateurs magiques (tout seul dans combat rapproché sinon)
				if(stripos($code, "scm2a")!==false) {
					$levels['arme2Hache'] = $level;
				}
				else if(stripos($code, "scm2m")!==false) {
					$levels['arme2Massue'] = $level;
				}
				else if(stripos($code, "scm2p")!==false) {
					$levels['arme2Pique'] = $level;
				}
				else if(stripos($code, "scm2s")!==false) {
					$levels['arme2Epee'] = $level;
				}
				else if(stripos($code, "scmc")!==false) {
					$levels['arme2Ampli'] = $level;
				}
				else {
					if($code == "scm2" || $code == "scm" || $code =="sc") {
						$levels['arme2Hache'] = $level;
						$levels['arme2Massue'] = $level;
						$levels['arme2Pique'] = $level;
						$levels['arme2Epee'] = $level;
						if($code == "scm" || $code =="sc") {
							$levels['arme2Ampli'] = $level;
						}
					}
				}
				break;
			default: // armes de tir
				if(stripos($code, "scr1")!==false) {
					$levels['armeTirHandgun'] = $level;
				}
				else if(stripos($code, "scr2a")!==false) {
					$levels['armeTirSubmachinegun'] = $level;
				}
				else if(stripos($code, "scr2l")!==false) {
					$levels['armeTirLg'] = $level;
				}
				else if(stripos($code, "scr2r")!==false) {
					$levels['armeTirShotgun'] = $level;
				}
				else {
					if($code == "scr2" || $code == "scr" || $code =="sc") {
						$levels['armeTirSubmachinegun'] = $level;
						$levels['armeTirLg'] = $level;
						$levels['armeTirShotgun'] = $level;
						if($code == "scr" || $code =="sc") {
							$levels['armeTirHandgun'] = $level;
						}
					}
				}
				break;
		}
	}
	return $levels;
}

function getMagicLevels($apiKey) {
	$allLevels = getHominMaxLevels($apiKey);
	if(isset($allLevels['error'])) {
		return $allLevels;
	}
	$levels = array(
		'heal' => -1,
		'neutra' => -1,
		'debi' => -1,
		'off' => -1
	);
	foreach($allLevels as $code => $level) {
		if(stripos($code, "smda")!==false) {
			$levels['neutra'] = $level;
		}
		else if(stripos($code, "smdh")!==false) {
			$levels['heal'] = $level;
		}
		else if(stripos($code, "smoa")!==false) {
			$levels['debi'] = $level;
		}
		else if(stripos($code, "smoe")!==false) {
			$levels['off'] = $level;
		}
		else {
			if($code == "smo" || $code == "smd") {
				if($code == "smo") {
					$levels['debi'] = $level;
					$levels['off'] = $level;
				}
				else {
					$levels['heal'] = $level;
					$levels['neutra'] = $level;
				}
			}
			else if($code == "sm") {
				$levels['heal'] = $level;
				$levels['neutra'] = $level;
				$levels['debi'] = $level;
				$levels['off'] = $level;
			}

		}
	}
	return $levels;
}

function getHarvestLevels($apiKey) {
	$allLevels = getHominMaxLevels($apiKey);
	if(isset($allLevels['error'])) {
		return $allLevels;
	}
	$levels = array(
		'desert' => -1,
		'forest' => -1,
		'jungle' => -1,
		'lakes' => -1,
		'primes' => -1
	);
	foreach($allLevels as $code => $level) {
		if(stripos($code, "shfd")!==false) {
			$levels['desert'] = $level;
		}
		else if(stripos($code, "shff")!==false) {
			$levels['forest'] = $level;
		}
		else if(stripos($code, "shfj")!==false) {
			$levels['jungle'] = $level;
		}
		else if(stripos($code, "shfl")!==false) {
			$levels['lakes'] = $level;
		}
		else if(stripos($code, "shfp")!==false) {
			$levels['primes'] = $level;
		}
		else {
			if($code == "shf" || $code == "sh") {
				$levels['desert'] = $level;
				$levels['forest'] = $level;
				$levels['jungle'] = $level;
				$levels['lakes'] = $level;
				$levels['primes'] = $level;
			}
		}
	}
	return $levels;
}

function getHominMaxLevels($apiKey) {
	$skillTree = ryzom_skilltree();
	$xml = ryzom_character_api($apiKey);
	if(isset($xml[$apiKey])) {
		$infos = $xml[$apiKey];
		if($infos === false || isset($infos->error)) {
			$error = array('error' => true, 'message' => "Character Key Error:", 'code' => -2);
			if(isset($infos->error)) {
				$error['code'] = (int) $infos->error['code'];
				$error['message'] .= (string) $infos->error;
			}
			return $error;
		}
		$skills = (Array) $infos->skills;
		$lvl;
		foreach($skills as $name => $value) {
			if($value<$skillTree[$name]['max'] || $value==250) {
				$lvl[$name] = $value;
			}
		}
		return $lvl;
	}
	else {
		$error = array('error' => true, 'message' => "Character Key Error:", 'code' => -2);
		if(isset($infos->error)) {
			$error['code'] = (int) $xml->error['code'];
			$error['message'] .= (string) $xml->error;
		}
		return $error;
	}
}

function generalTrad($index){
	$ligne = 'a:731:{s:19:"acuratebleedingshot";a:3:{s:4:"name";s:20:"Tir Précis Sanglant";s:1:"p";s:22:"Tirs Précis Sanglants";s:11:"description";s:0:"";}s:21:"acuratebreathlessshot";a:3:{s:4:"name";s:32:"Tir Précis à Couper le Souffle";s:1:"p";s:33:"Tirs Précis à Couper le Souffle";s:11:"description";s:0:"";}s:15:"animalslideslip";a:3:{s:4:"name";s:26:"Glissade/Flanc d\'un Animal";s:1:"p";s:25:"Glissades/Flanc d\'Animaux";s:11:"description";s:0:"";}s:14:"animalstalking";a:3:{s:4:"name";s:16:"Traque d\'Animaux";s:1:"p";s:17:"Traques d\'Animaux";s:11:"description";s:0:"";}s:10:"apothecary";a:3:{s:4:"name";s:11:"Apothicaire";s:1:"p";s:12:"Apothicaires";s:11:"description";s:0:"";}s:4:"area";a:3:{s:4:"name";s:4:"Zone";s:1:"p";s:5:"Zones";s:11:"description";s:0:"";}s:22:"armorandweaponsmithing";a:3:{s:4:"name";s:25:"Forge d\'Armure et d\'Armes";s:1:"p";s:27:"Forges d\'Armures et d\'Armes";s:11:"description";s:0:"";}s:11:"artandcraft";a:3:{s:4:"name";s:15:"Artisanat d\'Art";s:1:"p";s:15:"Artisanat d\'Art";s:11:"description";s:0:"";}s:20:"attackkamisknowledge";a:3:{s:4:"name";s:32:"Connaissance des Kamis d\'Attaque";s:1:"p";s:33:"Connaissances des Kamis d\'Attaque";s:11:"description";s:0:"";}s:19:"atysianregeneration";a:3:{s:4:"name";s:25:"Régénération Atysienne";s:1:"p";s:27:"Régénérations Atysiennes";s:11:"description";s:0:"";}s:3:"axe";a:3:{s:4:"name";s:5:"Hache";s:1:"p";s:6:"Haches";s:11:"description";s:0:"";}s:13:"backdraftstab";a:3:{s:4:"name";s:13:"Coup Explosif";s:1:"p";s:15:"Coups Explosifs";s:11:"description";s:0:"";}s:7:"badluck";a:3:{s:4:"name";s:9:"Malchance";s:1:"p";s:9:"Malchance";s:11:"description";s:0:"";}s:16:"barehandedcombat";a:3:{s:4:"name";s:20:"Combat à Mains Nues";s:1:"p";s:21:"Combats à Mains Nues";s:11:"description";s:0:"";}s:12:"bersekattack";a:3:{s:4:"name";s:16:"Attaque Démente";s:1:"p";s:18:"Attaques Démentes";s:11:"description";s:0:"";}s:13:"blacksmithing";a:3:{s:4:"name";s:8:"Forgeron";s:1:"p";s:9:"Forgerons";s:11:"description";s:0:"";}s:8:"bleeding";a:3:{s:4:"name";s:11:"Hémorragie";s:1:"p";s:12:"Hémorragies";s:11:"description";s:0:"";}s:12:"blindingrage";a:3:{s:4:"name";s:15:"Rage Aveuglante";s:1:"p";s:17:"Rages Aveuglantes";s:11:"description";s:0:"";}s:9:"blindshot";a:3:{s:4:"name";s:11:"Tir Aveugle";s:1:"p";s:13:"Tirs Aveugles";s:11:"description";s:0:"";}s:6:"bowgun";a:3:{s:4:"name";s:9:"Arbalète";s:1:"p";s:10:"Arbalètes";s:11:"description";s:0:"";}s:9:"bowpistol";a:3:{s:4:"name";s:18:"Pistolet-Arbalète";s:1:"p";s:20:"Pistolets-Arbalètes";s:11:"description";s:0:"";}s:8:"building";a:3:{s:4:"name";s:12:"Construction";s:1:"p";s:13:"Constructions";s:11:"description";s:0:"";}s:17:"bullyraggingblast";a:3:{s:4:"name";s:22:"Explosion Déchaînée";s:1:"p";s:24:"Explosions Déchaînées";s:11:"description";s:0:"";}s:10:"camouflage";a:3:{s:4:"name";s:10:"Camouflage";s:1:"p";s:11:"Camouflages";s:11:"description";s:0:"";}s:7:"caravan";a:3:{s:4:"name";s:7:"Karavan";s:1:"p";s:8:"Karavans";s:11:"description";s:0:"";}s:13:"cerebralfever";a:3:{s:4:"name";s:19:"Fièvre Cérébrale";s:1:"p";s:21:"Fièvres Cérébrales";s:11:"description";s:0:"";}s:14:"clothtailoring";a:3:{s:4:"name";s:7:"Couture";s:1:"p";s:8:"Coutures";s:11:"description";s:0:"";}s:7:"cooking";a:3:{s:4:"name";s:8:"Cuisiner";s:1:"p";s:8:"Cuisiner";s:11:"description";s:0:"";}s:14:"creaturehatred";a:3:{s:4:"name";s:20:"Haine des Créatures";s:1:"p";s:20:"Haine des Créatures";s:11:"description";s:0:"";}s:6:"dagger";a:3:{s:4:"name";s:5:"Dague";s:1:"p";s:6:"Dagues";s:11:"description";s:0:"";}s:6:"desert";a:3:{s:4:"name";s:7:"Désert";s:1:"p";s:8:"Déserts";s:11:"description";s:0:"";}s:32:"desertexoticrawmaterialknowledge";a:3:{s:4:"name";s:52:"Connaissance Matière Première Exotiques du Désert";s:1:"p";s:55:"Connaissances Matières Premières Exotiques du Désert";s:11:"description";s:0:"";}s:20:"desertkamisknowledge";a:3:{s:4:"name";s:33:"Connaissance des Kamis du Désert";s:1:"p";s:34:"Connaissances des Kamis du Désert";s:11:"description";s:0:"";}s:12:"deserttravel";a:3:{s:4:"name";s:22:"Voyage dans le Désert";s:1:"p";s:23:"Voyages dans le Désert";s:11:"description";s:0:"";}s:7:"digging";a:3:{s:4:"name";s:10:"Excavation";s:1:"p";s:11:"Excavations";s:11:"description";s:0:"";}s:16:"discreetapproach";a:3:{s:4:"name";s:18:"Approche Discrète";s:1:"p";s:20:"Approches Discrètes";s:11:"description";s:0:"";}s:6:"diving";a:3:{s:4:"name";s:8:"Plongeon";s:1:"p";s:9:"Plongeons";s:11:"description";s:0:"";}s:7:"dodging";a:3:{s:4:"name";s:7:"Esquive";s:1:"p";s:8:"Esquives";s:11:"description";s:0:"";}s:6:"dragon";a:3:{s:4:"name";s:6:"Dragon";s:1:"p";s:7:"Dragons";s:11:"description";s:0:"";}s:6:"dryade";a:3:{s:4:"name";s:6:"Dryade";s:1:"p";s:7:"Dryades";s:11:"description";s:0:"";}s:9:"dualwield";a:3:{s:4:"name";s:16:"Double Maniement";s:1:"p";s:18:"Doubles Maniements";s:11:"description";s:0:"";}s:8:"evaluate";a:3:{s:4:"name";s:10:"Evaluation";s:1:"p";s:11:"Evaluations";s:11:"description";s:0:"";}s:7:"farshot";a:3:{s:4:"name";s:12:"Tir Lointain";s:1:"p";s:14:"Tirs Lointains";s:11:"description";s:0:"";}s:8:"farthrow";a:3:{s:4:"name";s:15:"Lancer Lointain";s:1:"p";s:17:"Lancers Lointains";s:11:"description";s:0:"";}s:12:"feverprotect";a:3:{s:4:"name";s:28:"Protection contre la Fièvre";s:1:"p";s:29:"Protections contre la Fièvre";s:11:"description";s:0:"";}s:8:"firstaid";a:3:{s:4:"name";s:12:"Premier Soin";s:1:"p";s:14:"Premiers Soins";s:11:"description";s:0:"";}s:7:"fishing";a:3:{s:4:"name";s:6:"Pêche";s:1:"p";s:7:"Pêches";s:11:"description";s:0:"";}s:6:"forest";a:3:{s:4:"name";s:6:"Forêt";s:1:"p";s:7:"Forêts";s:11:"description";s:0:"";}s:32:"forestexoticrawmaterialknowledge";a:3:{s:4:"name";s:52:"Connaissance Matière Première Exotique des Forêts";s:1:"p";s:56:"Connaissances Matières Premières Exotiques des Forêts";s:11:"description";s:0:"";}s:20:"forestkamisknowledge";a:3:{s:4:"name";s:34:"Connaissance des Kamis des Forêts";s:1:"p";s:35:"Connaissances des Kamis des Forêts";s:11:"description";s:0:"";}s:12:"foresttravel";a:3:{s:4:"name";s:16:"Voyage en Forêt";s:1:"p";s:17:"Voyages en Forêt";s:11:"description";s:0:"";}s:5:"frost";a:3:{s:4:"name";s:3:"Gel";s:1:"p";s:4:"Gels";s:11:"description";s:0:"";}s:5:"fyros";a:3:{s:4:"name";s:5:"Fyros";s:1:"p";s:5:"Fyros";s:11:"description";s:0:"";}s:10:"fyrosgoods";a:3:{s:4:"name";s:11:"Biens Fyros";s:1:"p";s:11:"Biens Fyros";s:11:"description";s:0:"";}s:3:"goo";a:3:{s:4:"name";s:3:"Goo";s:1:"p";s:3:"Goo";s:11:"description";s:0:"";}s:8:"greataxe";a:3:{s:4:"name";s:12:"Grande Hache";s:1:"p";s:14:"Grandes Haches";s:11:"description";s:0:"";}s:9:"greatmass";a:3:{s:4:"name";s:13:"Grande Massue";s:1:"p";s:15:"Grandes Massues";s:11:"description";s:0:"";}s:7:"grenade";a:3:{s:4:"name";s:7:"Grenade";s:1:"p";s:8:"Grenades";s:11:"description";s:0:"";}s:13:"groundextract";a:3:{s:4:"name";s:19:"Extraction des Sols";s:1:"p";s:20:"Extractions des Sols";s:11:"description";s:0:"";}s:10:"handtohand";a:3:{s:4:"name";s:14:"Corps à Corps";s:1:"p";s:14:"Corps à Corps";s:11:"description";s:0:"";}s:7:"harpoon";a:3:{s:4:"name";s:7:"Harpon ";s:1:"p";s:7:"Harpons";s:11:"description";s:0:"";}s:14:"harpoonhunting";a:3:{s:4:"name";s:16:"Chasse au Harpon";s:1:"p";s:17:"Chasses au Harpon";s:11:"description";s:0:"";}s:7:"harvest";a:3:{s:4:"name";s:8:"Récolte";s:1:"p";s:9:"Récoltes";s:11:"description";s:0:"";}s:21:"healingkamisknowledge";a:3:{s:4:"name";s:35:"Connaissance des Kamis de Guérison";s:1:"p";s:36:"Connaissances des Kamis de Guérison";s:11:"description";s:0:"";}s:13:"hearwithering";a:3:{s:4:"name";s:26:"Affaiblissement de l\'Ouïe";s:1:"p";s:27:"Affaiblissements de l\'Ouïe";s:11:"description";s:0:"";}s:15:"heavyarmourwear";a:3:{s:4:"name";s:28:"Vêtement pour Armure Lourde";s:1:"p";s:31:"Vêtements pour Armures Lourdes";s:11:"description";s:0:"";}s:11:"heavyweapon";a:3:{s:4:"name";s:11:"Arme Lourde";s:1:"p";s:13:"Armes Lourdes";s:11:"description";s:0:"";}s:4:"hide";a:3:{s:4:"name";s:8:"Cachette";s:1:"p";s:9:"Cachettes";s:11:"description";s:0:"";}s:27:"hominopponentconsiderweapon";a:3:{s:4:"name";s:20:"Considération Homin";s:1:"p";s:22:"Considérations Homins";s:11:"description";s:0:"";}s:21:"huntingkamisknowledge";a:3:{s:4:"name";s:32:"Connaissance des Kamis Chasseurs";s:1:"p";s:33:"Connaissances des Kamis Chasseurs";s:11:"description";s:0:"";}s:9:"jewellery";a:3:{s:4:"name";s:6:"Bijoux";s:1:"p";s:6:"Bijoux";s:11:"description";s:0:"";}s:6:"jungle";a:3:{s:4:"name";s:6:"Jungle";s:1:"p";s:7:"Jungles";s:11:"description";s:0:"";}s:32:"jungleexoticrawmaterialknowledge";a:3:{s:4:"name";s:53:"Connaissance Matière Première Exotique de la Jungle";s:1:"p";s:57:"Connaissances Matières Premières Exotiques de la Jungle";s:11:"description";s:0:"";}s:20:"junglekamisknowledge";a:3:{s:4:"name";s:35:"Connaissance des Kamis de la Jungle";s:1:"p";s:36:"Connaissances des Kamis de la Jungle";s:11:"description";s:0:"";}s:12:"jungletravel";a:3:{s:4:"name";s:21:"Voyage dans la Jungle";s:1:"p";s:22:"Voyages dans la Jungle";s:11:"description";s:0:"";}s:4:"kami";a:3:{s:4:"name";s:4:"Kami";s:1:"p";s:5:"Kamis";s:11:"description";s:0:"";}s:11:"kamicshield";a:3:{s:4:"name";s:14:"Bouclier Kamic";s:1:"p";s:16:"Boucliers Kamics";s:11:"description";s:0:"";}s:10:"kamicshock";a:3:{s:4:"name";s:10:"Choc Kamic";s:1:"p";s:12:"Chocs Kamics";s:11:"description";s:0:"";}s:4:"kick";a:3:{s:4:"name";s:12:"Coup de Pied";s:1:"p";s:13:"Coups de Pied";s:11:"description";s:0:"";}s:5:"kitin";a:3:{s:4:"name";s:5:"Kitin";s:1:"p";s:6:"Kitins";s:11:"description";s:0:"";}s:4:"lake";a:3:{s:4:"name";s:3:"Lac";s:1:"p";s:4:"Lacs";s:11:"description";s:0:"";}s:9:"lakearmor";a:3:{s:4:"name";s:15:"Armure des Lacs";s:1:"p";s:16:"Armures des Lacs";s:11:"description";s:0:"";}s:30:"lakeexoticrawmaterialknowledge";a:3:{s:4:"name";s:49:"Connaissance Matière Première Exotique des Lacs";s:1:"p";s:53:"Connaissances Matières Premières Exotiques des Lacs";s:11:"description";s:0:"";}s:18:"lakekamisknowledge";a:3:{s:4:"name";s:31:"Connaissance des Kamis des Lacs";s:1:"p";s:32:"Connaissances des Kamis des Lacs";s:11:"description";s:0:"";}s:10:"laketravel";a:3:{s:4:"name";s:20:"Voyage dans les Lacs";s:1:"p";s:21:"Voyages dans les Lacs";s:11:"description";s:0:"";}s:10:"leadanimal";a:3:{s:4:"name";s:15:"Animal de Tête";s:1:"p";s:16:"Animaux de Tête";s:11:"description";s:0:"";}s:16:"leathertailoring";a:3:{s:4:"name";s:18:"Traitement du Cuir";s:1:"p";s:19:"Traitements du Cuir";s:11:"description";s:0:"";}s:16:"lefthandedattack";a:3:{s:4:"name";s:25:"Attaque de la Main Gauche";s:1:"p";s:26:"Attaques de la Main Gauche";s:11:"description";s:0:"";}s:15:"lefthandedparry";a:3:{s:4:"name";s:24:"Parade de la Main Gauche";s:1:"p";s:25:"Parades de la Main Gauche";s:11:"description";s:0:"";}s:11:"lifealchemy";a:3:{s:4:"name";s:18:"Alchimie de la Vie";s:1:"p";s:19:"Alchimies de la Vie";s:11:"description";s:0:"";}s:8:"lifegift";a:3:{s:4:"name";s:10:"Don de Vie";s:1:"p";s:11:"Dons de Vie";s:11:"description";s:0:"";}s:15:"lightarmourwear";a:3:{s:4:"name";s:30:"Vêtement pour Armure Légère";s:1:"p";s:33:"Vêtements pour Armures Légères";s:11:"description";s:0:"";}s:7:"madness";a:3:{s:4:"name";s:5:"Folie";s:1:"p";s:5:"Folie";s:11:"description";s:0:"";}s:12:"magicobjects";a:3:{s:4:"name";s:13:"Objet Magique";s:1:"p";s:15:"Objets Magiques";s:11:"description";s:0:"";}s:14:"magictransfert";a:3:{s:4:"name";s:17:"Transfert Magique";s:1:"p";s:19:"Transferts Magiques";s:11:"description";s:0:"";}s:11:"majordryade";a:3:{s:4:"name";s:14:"Dryade Majeure";s:1:"p";s:16:"Dryades Majeures";s:11:"description";s:0:"";}s:13:"majorheallife";a:3:{s:4:"name";s:24:"Guérison Majeure de Vie";s:1:"p";s:26:"Guérisons Majeures de Vie";s:11:"description";s:0:"";}s:21:"majorliferegeneration";a:3:{s:4:"name";s:30:"Régénération Majeure de Vie";s:1:"p";s:32:"Régénérations Majeures de Vie";s:11:"description";s:0:"";}s:15:"majormandragore";a:3:{s:4:"name";s:18:"Mandragore Majeure";s:1:"p";s:20:"Mandragores Majeures";s:11:"description";s:0:"";}s:12:"majorsapheal";a:3:{s:4:"name";s:26:"Guérison Majeure de Sève";s:1:"p";s:28:"Guérisons Majeures de Sève";s:11:"description";s:0:"";}s:21:"majorsapregenearation";a:3:{s:4:"name";s:32:"Régénération Majeure de Sève";s:1:"p";s:34:"Régénérations Majeures de Sève";s:11:"description";s:0:"";}s:10:"majorsylve";a:3:{s:4:"name";s:13:"Sylve Majeure";s:1:"p";s:15:"Sylves Majeures";s:11:"description";s:0:"";}s:18:"majortirednessheal";a:3:{s:4:"name";s:28:"Guérison Majeure de Fatigue";s:1:"p";s:30:"Guérisons Majeures de Fatigue";s:11:"description";s:0:"";}s:27:"majortirednessregenearation";a:3:{s:4:"name";s:34:"Régénération Majeure de Fatigue";s:1:"p";s:36:"Régénérations Majeures de Fatigue";s:11:"description";s:0:"";}s:10:"makeanklet";a:3:{s:4:"name";s:35:"Fabrication d\'un Anneau de Cheville";s:1:"p";s:34:"Fabrications d\'Anneaux de Cheville";s:11:"description";s:0:"";}s:14:"makeautolaunch";a:3:{s:4:"name";s:30:"Fabrication d\'une Mitrailleuse";s:1:"p";s:28:"Fabrications d\'Mitrailleuses";s:11:"description";s:0:"";}s:18:"makeautolaunchammo";a:3:{s:4:"name";s:31:"Fabr. de Munitions Mitrailleuse";s:1:"p";s:32:"Fabr. de Munitions Mitrailleuses";s:11:"description";s:0:"";}s:7:"makeaxe";a:3:{s:4:"name";s:23:"Fabrication d\'une Hache";s:1:"p";s:22:"Fabrications de Haches";s:11:"description";s:0:"";}s:7:"makebag";a:3:{s:4:"name";s:20:"Fabrication d\'un Sac";s:1:"p";s:20:"Fabrications de Sacs";s:11:"description";s:0:"";}s:10:"makebowgun";a:3:{s:4:"name";s:27:"Fabrication d\'une Arbalète";s:1:"p";s:25:"Fabrications d\'Arbalètes";s:11:"description";s:0:"";}s:14:"makebowgunammo";a:3:{s:4:"name";s:34:"Fabrication de Munitions Arbalète";s:1:"p";s:36:"Fabrications de Munitions Arbalètes";s:11:"description";s:0:"";}s:12:"makebracelet";a:3:{s:4:"name";s:25:"Fabrication d\'un Bracelet";s:1:"p";s:25:"Fabrications de Bracelets";s:11:"description";s:0:"";}s:11:"makebuckler";a:3:{s:4:"name";s:26:"Fabrication d\'une Rondache";s:1:"p";s:25:"Fabrications de Rondaches";s:11:"description";s:0:"";}s:11:"makeclothes";a:3:{s:4:"name";s:25:"Fabrication de Vêtements";s:1:"p";s:26:"Fabrications de Vêtements";s:11:"description";s:0:"";}s:10:"makedagger";a:3:{s:4:"name";s:23:"Fabrication d\'une Dague";s:1:"p";s:22:"Fabrications de Dagues";s:11:"description";s:0:"";}s:10:"makediadem";a:3:{s:4:"name";s:25:"Fabrication d\'un Diadème";s:1:"p";s:25:"Fabrications de Diadèmes";s:11:"description";s:0:"";}s:10:"makeearing";a:3:{s:4:"name";s:34:"Fabrication d\'une Boucle d\'Oreille";s:1:"p";s:33:"Fabrications de Boucles d\'Oreille";s:11:"description";s:0:"";}s:13:"makefireammos";a:3:{s:4:"name";s:37:"Fabrication de Munitions Incendiaires";s:1:"p";s:38:"Fabrications de Munitions Incendiaires";s:11:"description";s:0:"";}s:23:"makefiretoughenedarmors";a:3:{s:4:"name";s:37:"Fabr. d\'une Armure Forgée par le Feu";s:1:"p";s:35:"Fabr. d\'Armures Forgées par le Feu";s:11:"description";s:0:"";}s:15:"makefireweapons";a:3:{s:4:"name";s:29:"Fabrication d\'une Arme à Feu";s:1:"p";s:27:"Fabrications d\'Armes à Feu";s:11:"description";s:0:"";}s:22:"makeflorallivingjewels";a:3:{s:4:"name";s:36:"Fabrication d\'un Bijou Floral Vivant";s:1:"p";s:31:"Fabr. de Bijoux Floraux Vivants";s:11:"description";s:0:"";}s:30:"makefloralpatternlivingclothes";a:3:{s:4:"name";s:35:"Fabr. de Vêtements Floraux Vivants";s:1:"p";s:35:"Fabr. de Vêtements Floraux Vivants";s:11:"description";s:0:"";}s:11:"makegrenade";a:3:{s:4:"name";s:25:"Fabrication d\'une Grenade";s:1:"p";s:24:"Fabrications de Grenades";s:11:"description";s:0:"";}s:14:"makeharpoongun";a:3:{s:4:"name";s:26:"Fabr. d\'un Pistolet-Harpon";s:1:"p";s:26:"Fabr. de Pistolets-Harpons";s:11:"description";s:0:"";}s:18:"makeharpoongunammo";a:3:{s:4:"name";s:34:"Fabr. de Munitions Pistolet-Harpon";s:1:"p";s:39:"Fabr. de Munitions de Pistolets-Harpons";s:11:"description";s:0:"";}s:15:"makeheavyarmors";a:3:{s:4:"name";s:31:"Fabrication d\'une Armure Lourde";s:1:"p";s:30:"Fabrications d\'Armures Lourdes";s:11:"description";s:0:"";}s:14:"makeheavyboots";a:3:{s:4:"name";s:29:"Fabrication de Bottes Lourdes";s:1:"p";s:30:"Fabrications de Bottes Lourdes";s:11:"description";s:0:"";}s:15:"makeheavygloves";a:3:{s:4:"name";s:27:"Fabrication de Gants Lourds";s:1:"p";s:28:"Fabrications de Gants Lourds";s:11:"description";s:0:"";}s:15:"makeheavyhelmet";a:3:{s:4:"name";s:29:"Fabrication d\'un Casque Lourd";s:1:"p";s:30:"Fabrications de Casques Lourds";s:11:"description";s:0:"";}s:14:"makeheavypants";a:3:{s:4:"name";s:31:"Fabrication d\'un Pantalon Lourd";s:1:"p";s:32:"Fabrications de Pantalons Lourds";s:11:"description";s:0:"";}s:16:"makeheavysleeves";a:3:{s:4:"name";s:30:"Fabrication de Manches Lourdes";s:1:"p";s:31:"Fabrications de Manches Lourdes";s:11:"description";s:0:"";}s:13:"makeheavyvest";a:3:{s:4:"name";s:28:"Fabrication d\'un Gilet Lourd";s:1:"p";s:29:"Fabrications de Gilets Lourds";s:11:"description";s:0:"";}s:10:"makejewels";a:3:{s:4:"name";s:21:"Fabrication de Bijoux";s:1:"p";s:22:"Fabrications de Bijoux";s:11:"description";s:0:"";}s:16:"makekamicclothes";a:3:{s:4:"name";s:31:"Fabrication de Vêtements Kamis";s:1:"p";s:32:"Fabrications de Vêtements Kamis";s:11:"description";s:0:"";}s:15:"makekamicjewels";a:3:{s:4:"name";s:27:"Fabrication d\'un Bijou Kami";s:1:"p";s:28:"Fabrications de Bijoux Kamis";s:11:"description";s:0:"";}s:15:"makekamicweapon";a:3:{s:4:"name";s:27:"Fabrication d\'une Arme Kami";s:1:"p";s:26:"Fabrications d\'Armes Kamis";s:11:"description";s:0:"";}s:9:"makelance";a:3:{s:4:"name";s:28:"Fabrication d\'une Lance Kami";s:1:"p";s:28:"Fabrications de Lances Kamis";s:11:"description";s:0:"";}s:12:"makelauncher";a:3:{s:4:"name";s:36:"Fabrication d\'un Lance-grenades Kami";s:1:"p";s:36:"Fabrications de Lance-grenades Kamis";s:11:"description";s:0:"";}s:16:"makelauncherammo";a:3:{s:4:"name";s:38:"Fabr. de Munitions Lance-grenades Kami";s:1:"p";s:42:"Fabr. de Munitions de Lance-grenades Kamis";s:11:"description";s:0:"";}s:14:"makelightboots";a:3:{s:4:"name";s:31:"Fabrication de Bottes Légères";s:1:"p";s:32:"Fabrications de Bottes Légères";s:11:"description";s:0:"";}s:15:"makelightgloves";a:3:{s:4:"name";s:28:"Fabrication de Gants Légers";s:1:"p";s:29:"Fabrications de Gants Légers";s:11:"description";s:0:"";}s:15:"makelighthelmet";a:3:{s:4:"name";s:30:"Fabrication d\'un Casque Léger";s:1:"p";s:31:"Fabrications de Casques Légers";s:11:"description";s:0:"";}s:14:"makelightpants";a:3:{s:4:"name";s:32:"Fabrication d\'un Pantalon Léger";s:1:"p";s:33:"Fabrications de Pantalons Légers";s:11:"description";s:0:"";}s:16:"makelightsleeves";a:3:{s:4:"name";s:32:"Fabrication de Manches Légères";s:1:"p";s:33:"Fabrications de Manches Légères";s:11:"description";s:0:"";}s:13:"makelightvest";a:3:{s:4:"name";s:29:"Fabrication d\'un Gilet Léger";s:1:"p";s:30:"Fabrications de Gilets Légers";s:11:"description";s:0:"";}s:11:"makelongaxe";a:3:{s:4:"name";s:29:"Fabrication d\'une Hache à 2M";s:1:"p";s:28:"Fabrications de Haches à 2M";s:11:"description";s:0:"";}s:12:"makelongmace";a:3:{s:4:"name";s:30:"Fabrication d\'une Massue à 2M";s:1:"p";s:29:"Fabrications de Massues à 2M";s:11:"description";s:0:"";}s:13:"makelongsword";a:3:{s:4:"name";s:29:"Fabrication d\'une Epée à 2M";s:1:"p";s:27:"Fabrications d\'Epées à 2M";s:11:"description";s:0:"";}s:8:"makemace";a:3:{s:4:"name";s:24:"Fabrication d\'une Massue";s:1:"p";s:23:"Fabrications de Massues";s:11:"description";s:0:"";}s:16:"makemediumarmors";a:3:{s:4:"name";s:26:"Fabr. d\'une Armure Moyenne";s:1:"p";s:24:"Fabr. d\'Armures Moyennes";s:11:"description";s:0:"";}s:15:"makemediumboots";a:3:{s:4:"name";s:24:"Fabr. de Bottes Moyennes";s:1:"p";s:24:"Fabr. de Bottes Moyennes";s:11:"description";s:0:"";}s:16:"makemediumgloves";a:3:{s:4:"name";s:21:"Fabr. de Gants Moyens";s:1:"p";s:21:"Fabr. de Gants Moyens";s:11:"description";s:0:"";}s:16:"makemediumhelmet";a:3:{s:4:"name";s:23:"Fabr. d\'un Casque Moyen";s:1:"p";s:25:"Fabr. de Casques Moyennes";s:11:"description";s:0:"";}s:15:"makemediumpants";a:3:{s:4:"name";s:25:"Fabr. d\'un Pantalon Moyen";s:1:"p";s:25:"Fabr. de Pantalons Moyens";s:11:"description";s:0:"";}s:17:"makemediumsleeves";a:3:{s:4:"name";s:25:"Fabr. de Manches Moyennes";s:1:"p";s:25:"Fabr. de Manches Moyennes";s:11:"description";s:0:"";}s:14:"makemediumvest";a:3:{s:4:"name";s:22:"Fabr. d\'un Gilet Moyen";s:1:"p";s:22:"Fabr. de Gilets Moyens";s:11:"description";s:0:"";}s:16:"makemeleeweapons";a:3:{s:4:"name";s:33:"Fabrication d\'une Arme de Mêlée";s:1:"p";s:31:"Fabrications d\'Armes de Mêlée";s:11:"description";s:0:"";}s:11:"makependant";a:3:{s:4:"name";s:26:"Fabrication d\'un Pendentif";s:1:"p";s:26:"Fabrications de Pendentifs";s:11:"description";s:0:"";}s:8:"makepike";a:3:{s:4:"name";s:23:"Fabrication d\'une Pique";s:1:"p";s:22:"Fabrications de Piques";s:11:"description";s:0:"";}s:10:"makepistol";a:3:{s:4:"name";s:25:"Fabrication d\'un Pistolet";s:1:"p";s:25:"Fabrications de Pistolets";s:11:"description";s:0:"";}s:14:"makepistolammo";a:3:{s:4:"name";s:33:"Fabrication de Munitions Pistolet";s:1:"p";s:38:"Fabrications de Munitions de Pistolets";s:11:"description";s:0:"";}s:13:"makepistolarc";a:3:{s:4:"name";s:35:"Fabrication d\'un Pistolet-Arbalète";s:1:"p";s:36:"Fabrications de Pistolets-Arbalètes";s:11:"description";s:0:"";}s:17:"makepistolarcammo";a:3:{s:4:"name";s:37:"Fabr. de Munitions Pistolet-Arbalète";s:1:"p";s:39:"Fabr. de Munitions Pistolets-Arbalètes";s:11:"description";s:0:"";}s:27:"makepoisonnouslivingweapons";a:3:{s:4:"name";s:38:"Fabr. d\'une Arme Empoisonnée Vivante ";s:1:"p";s:36:"Fabr. d\'Armes Empoisonnées Vivantes";s:11:"description";s:0:"";}s:20:"makerangeweaponammos";a:3:{s:4:"name";s:30:"Fabr. de Munitions Arme de Tir";s:1:"p";s:31:"Fabr. de Munitions Armes de Tir";s:11:"description";s:0:"";}s:16:"makerangeweapons";a:3:{s:4:"name";s:29:"Fabrication d\'une Arme de Tir";s:1:"p";s:27:"Fabrications d\'Armes de Tir";s:11:"description";s:0:"";}s:9:"makerifle";a:3:{s:4:"name";s:22:"Fabrication d\'un Fusil";s:1:"p";s:22:"Fabrications de Fusils";s:11:"description";s:0:"";}s:13:"makerifleammo";a:3:{s:4:"name";s:30:"Fabrication de Munitions Fusil";s:1:"p";s:32:"Fabrications de Munitions Fusils";s:11:"description";s:0:"";}s:8:"makering";a:3:{s:4:"name";s:23:"Fabrication d\'une Bague";s:1:"p";s:22:"Fabrications de Bagues";s:11:"description";s:0:"";}s:10:"makeshield";a:3:{s:4:"name";s:25:"Fabrication d\'un Bouclier";s:1:"p";s:25:"Fabrications de Boucliers";s:11:"description";s:0:"";}s:15:"makeshiftrepair";a:3:{s:4:"name";s:22:"Réparation de Fortune";s:1:"p";s:23:"Réparations de Fortune";s:11:"description";s:0:"";}s:17:"makesurvivaltools";a:3:{s:4:"name";s:32:"Fabrication d\'un Outil de Survie";s:1:"p";s:31:"Fabrications d\'Outils de Survie";s:11:"description";s:0:"";}s:18:"makesurvivalweapon";a:3:{s:4:"name";s:32:"Fabrication d\'une Arme de Survie";s:1:"p";s:30:"Fabrications d\'Armes de Survie";s:11:"description";s:0:"";}s:9:"makesword";a:3:{s:4:"name";s:23:"Fabrication d\'une Epée";s:1:"p";s:21:"Fabrications d\'Epées";s:11:"description";s:0:"";}s:18:"makethrowingweapon";a:3:{s:4:"name";s:32:"Fabrication d\'une Arme de Lancer";s:1:"p";s:30:"Fabrications d\'Armes de Lancer";s:11:"description";s:0:"";}s:9:"maketools";a:3:{s:4:"name";s:22:"Fabrication d\'un Outil";s:1:"p";s:21:"Fabrications d\'Outils";s:11:"description";s:0:"";}s:25:"maketwo_handsmeleeweapons";a:3:{s:4:"name";s:39:"Fabrication d\'une Arme de Mêlée à 2M";s:1:"p";s:37:"Fabrications d\'Armes de Mêlée à 2M";s:11:"description";s:0:"";}s:29:"maketwo_handsrangeweaponammos";a:3:{s:4:"name";s:39:"Fabr. de Munitions d\'Armes de Tir à 2M";s:1:"p";s:42:"Fabr. de Munitions d\'Armes de Mêlée à M";s:11:"description";s:0:"";}s:25:"maketwo_handsrangeweapons";a:3:{s:4:"name";s:35:"Fabrication d\'une Arme de Tir à 2M";s:1:"p";s:33:"Fabrications d\'Armes de Tir à 2M";s:11:"description";s:0:"";}s:10:"mandragore";a:3:{s:4:"name";s:10:"Mandragore";s:1:"p";s:11:"Mandragores";s:11:"description";s:0:"";}s:12:"mandrakefist";a:3:{s:4:"name";s:19:"Point de Mandragore";s:1:"p";s:21:"Points de Mandragores";s:11:"description";s:0:"";}s:4:"mass";a:3:{s:4:"name";s:6:"Massue";s:1:"p";s:7:"Massues";s:11:"description";s:0:"";}s:12:"massivethrow";a:3:{s:4:"name";s:13:"Lancer Massif";s:1:"p";s:15:"Lancers Massifs";s:11:"description";s:0:"";}s:5:"matis";a:3:{s:4:"name";s:5:"Matis";s:1:"p";s:5:"Matis";s:11:"description";s:0:"";}s:10:"matisgoods";a:3:{s:4:"name";s:11:"Biens Matis";s:1:"p";s:11:"Biens Matis";s:11:"description";s:0:"";}s:12:"matismystery";a:3:{s:4:"name";s:14:"Mystère Matis";s:1:"p";s:15:"Mystères Matis";s:11:"description";s:0:"";}s:8:"mechanic";a:3:{s:4:"name";s:11:"Mécanicien";s:1:"p";s:12:"Mécaniciens";s:11:"description";s:0:"";}s:16:"mediumarmourwear";a:3:{s:4:"name";s:29:"Vêtement pour Armure Moyenne";s:1:"p";s:27:"Vêtements Armures Moyennes";s:11:"description";s:0:"";}s:13:"minorlifeheal";a:3:{s:4:"name";s:24:"Guérison Mineure de Vie";s:1:"p";s:26:"Guérisons Mineures de Vie";s:11:"description";s:0:"";}s:12:"minorsapheal";a:3:{s:4:"name";s:26:"Guérison Mineure de Sève";s:1:"p";s:28:"Guérisons Mineures de Sève";s:11:"description";s:0:"";}s:18:"minortirednessheal";a:3:{s:4:"name";s:28:"Guérison Mineure de Fatigue";s:1:"p";s:30:"Guérisons Mineures de Fatigue";s:11:"description";s:0:"";}s:6:"mirror";a:3:{s:4:"name";s:6:"Miroir";s:1:"p";s:7:"Miroirs";s:11:"description";s:0:"";}s:13:"muscularfever";a:3:{s:4:"name";s:18:"Fièvre Musculaire";s:1:"p";s:20:"Fièvres Musculaires";s:11:"description";s:0:"";}s:12:"nervousfever";a:3:{s:4:"name";s:16:"Fièvre Nerveuse";s:1:"p";s:18:"Fièvres Nerveuses";s:11:"description";s:0:"";}s:9:"nightshot";a:3:{s:4:"name";s:11:"Tir de Nuit";s:1:"p";s:13:"Tirs de Nuits";s:11:"description";s:0:"";}s:4:"nunb";a:3:{s:4:"name";s:15:"Engourdissement";s:1:"p";s:16:"Engourdissements";s:11:"description";s:0:"";}s:16:"onhorsebackmelee";a:3:{s:4:"name";s:25:"Combat sur Dos de Mektoub";s:1:"p";s:27:"Combats sur Dos de Mektoubs";s:11:"description";s:0:"";}s:16:"onhorsebackshoot";a:3:{s:4:"name";s:28:"Fusillade sur Dos de Mektoub";s:1:"p";s:30:"Fusillades sur Dos de Mektoubs";s:11:"description";s:0:"";}s:9:"painshare";a:3:{s:4:"name";s:21:"Partage de la Douleur";s:1:"p";s:22:"Partages de la Douleur";s:11:"description";s:0:"";}s:9:"paralysis";a:3:{s:4:"name";s:9:"Paralysie";s:1:"p";s:10:"Paralysies";s:11:"description";s:0:"";}s:4:"pike";a:3:{s:4:"name";s:5:"Pique";s:1:"p";s:6:"Piques";s:11:"description";s:0:"";}s:11:"pikehunting";a:3:{s:4:"name";s:20:"Chasse de Créatures";s:1:"p";s:21:"Chasses de Créatures";s:11:"description";s:0:"";}s:6:"pistol";a:3:{s:4:"name";s:8:"Pistolet";s:1:"p";s:9:"Pistolets";s:11:"description";s:0:"";}s:11:"planthatred";a:3:{s:4:"name";s:17:"Haine des Plantes";s:1:"p";s:17:"Haine des Plantes";s:11:"description";s:0:"";}s:14:"pointblankshot";a:3:{s:4:"name";s:19:"Tir à Bout Portant";s:1:"p";s:20:"Tirs à Bout Portant";s:11:"description";s:0:"";}s:12:"primaryroots";a:3:{s:4:"name";s:12:"Prime Racine";s:1:"p";s:14:"Primes Racines";s:11:"description";s:0:"";}s:23:"protectingkamisknoledge";a:3:{s:4:"name";s:33:"Connaissance de Kamis Protecteurs";s:1:"p";s:34:"Connaissances de Kamis Protecteurs";s:11:"description";s:0:"";}s:10:"quartering";a:3:{s:4:"name";s:10:"Dépeçage";s:1:"p";s:11:"Dépeçages";s:11:"description";s:0:"";}s:9:"quickshot";a:3:{s:4:"name";s:10:"Tir Rapide";s:1:"p";s:12:"Tirs Rapides";s:11:"description";s:0:"";}s:10:"quickthrow";a:3:{s:4:"name";s:13:"Lancer Rapide";s:1:"p";s:15:"Lancers Rapides";s:11:"description";s:0:"";}s:5:"range";a:3:{s:4:"name";s:7:"Portée";s:1:"p";s:8:"Portées";s:11:"description";s:0:"";}s:10:"rapidburst";a:3:{s:4:"name";s:16:"Explosion Rapide";s:1:"p";s:18:"Explosions Rapides";s:11:"description";s:0:"";}s:6:"resist";a:3:{s:4:"name";s:9:"Résister";s:1:"p";s:9:"Résister";s:11:"description";s:0:"";}s:10:"resistance";a:3:{s:4:"name";s:11:"Résistance";s:1:"p";s:12:"Résistances";s:11:"description";s:0:"";}s:11:"ridepackers";a:3:{s:4:"name";s:22:"Premier Animal de Bât";s:1:"p";s:24:"Premiers Animaux de Bât";s:11:"description";s:0:"";}s:6:"riding";a:3:{s:4:"name";s:5:"Monte";s:1:"p";s:6:"Montes";s:11:"description";s:0:"";}s:5:"rifle";a:3:{s:4:"name";s:5:"Fusil";s:1:"p";s:6:"Fusils";s:11:"description";s:0:"";}s:12:"riflehunting";a:3:{s:4:"name";s:9:"Fusillade";s:1:"p";s:10:"Fusillades";s:11:"description";s:0:"";}s:6:"rocket";a:3:{s:4:"name";s:8:"Roquette";s:1:"p";s:9:"Roquettes";s:11:"description";s:0:"";}s:9:"rocktabou";a:3:{s:4:"name";s:10:"Rock Taboo";s:1:"p";s:11:"Rock Taboos";s:11:"description";s:0:"";}s:8:"rondache";a:3:{s:4:"name";s:8:"Rondache";s:1:"p";s:9:"Rondaches";s:11:"description";s:0:"";}s:3:"rot";a:3:{s:4:"name";s:10:"Pourriture";s:1:"p";s:11:"Pourritures";s:11:"description";s:0:"";}s:7:"running";a:3:{s:4:"name";s:6:"Course";s:1:"p";s:7:"Courses";s:11:"description";s:0:"";}s:10:"sapalchemy";a:3:{s:4:"name";s:20:"Alchimie de la Sève";s:1:"p";s:21:"Alchimies de la Sève";s:11:"description";s:0:"";}s:8:"saparmor";a:3:{s:4:"name";s:15:"Armure de Sève";s:1:"p";s:16:"Armures de Sève";s:11:"description";s:0:"";}s:7:"sapgift";a:3:{s:4:"name";s:12:"Don de Sève";s:1:"p";s:13:"Dons de Sève";s:11:"description";s:0:"";}s:6:"shield";a:3:{s:4:"name";s:8:"Bouclier";s:1:"p";s:9:"Boucliers";s:11:"description";s:0:"";}s:11:"shootandrun";a:3:{s:4:"name";s:23:"Tirer en se déplaçant";s:1:"p";s:23:"Tirer en se déplaçant";s:11:"description";s:0:"";}s:11:"shotprotect";a:3:{s:4:"name";s:17:"Protection de Tir";s:1:"p";s:18:"Protections de Tir";s:11:"description";s:0:"";}s:13:"slowingbreath";a:3:{s:4:"name";s:23:"Ralentisseur de Souffle";s:1:"p";s:24:"Ralentisseurs de Souffle";s:11:"description";s:0:"";}s:11:"slowinglife";a:3:{s:4:"name";s:19:"Ralentisseur de Vie";s:1:"p";s:20:"Ralentisseurs de Vie";s:11:"description";s:0:"";}s:5:"spear";a:3:{s:4:"name";s:5:"Lance";s:1:"p";s:6:"Lances";s:11:"description";s:0:"";}s:13:"spelldeletion";a:3:{s:4:"name";s:21:"Suppression d\'un Sort";s:1:"p";s:21:"Suppressions de Sorts";s:11:"description";s:0:"";}s:12:"spellprotect";a:3:{s:4:"name";s:20:"Protection d\'un Sort";s:1:"p";s:20:"Protections de Sorts";s:11:"description";s:0:"";}s:12:"spurtingfire";a:3:{s:4:"name";s:15:"Feu Jaillissant";s:1:"p";s:17:"Feux Jaillissants";s:11:"description";s:0:"";}s:11:"squandering";a:3:{s:4:"name";s:10:"Gaspillage";s:1:"p";s:11:"Gaspillages";s:11:"description";s:0:"";}s:10:"stallsetup";a:3:{s:4:"name";s:20:"Montage d\'une Stalle";s:1:"p";s:19:"Montages de Stalles";s:11:"description";s:0:"";}s:11:"stealbreath";a:3:{s:4:"name";s:14:"Souffle Voleur";s:1:"p";s:16:"Souffles Voleurs";s:11:"description";s:0:"";}s:9:"steallife";a:3:{s:4:"name";s:11:"Vie Voleuse";s:1:"p";s:13:"Vies Voleuses";s:11:"description";s:0:"";}s:8:"stealsap";a:3:{s:4:"name";s:13:"Sève Voleuse";s:1:"p";s:13:"Sève Voleuse";s:11:"description";s:0:"";}s:5:"stick";a:3:{s:4:"name";s:6:"Bâton";s:1:"p";s:7:"Bâtons";s:11:"description";s:0:"";}s:12:"strenghtgift";a:3:{s:4:"name";s:12:"Don de Force";s:1:"p";s:13:"Dons de Force";s:11:"description";s:0:"";}s:8:"swimming";a:3:{s:4:"name";s:4:"Nage";s:1:"p";s:5:"Nages";s:11:"description";s:0:"";}s:5:"sword";a:3:{s:4:"name";s:5:"Epée";s:1:"p";s:6:"Epées";s:11:"description";s:0:"";}s:12:"sylphicslide";a:3:{s:4:"name";s:20:"Glissement Sylphique";s:1:"p";s:22:"Glissements Sylphiques";s:11:"description";s:0:"";}s:5:"sylve";a:3:{s:4:"name";s:5:"Sylve";s:1:"p";s:6:"Sylves";s:11:"description";s:0:"";}s:16:"tactilewithering";a:3:{s:4:"name";s:26:"Affaiblissement du Toucher";s:1:"p";s:27:"Affaiblissements du Toucher";s:11:"description";s:0:"";}s:6:"target";a:3:{s:4:"name";s:5:"Cible";s:1:"p";s:6:"Cibles";s:11:"description";s:0:"";}s:6:"terror";a:3:{s:4:"name";s:7:"Terreur";s:1:"p";s:8:"Terreurs";s:11:"description";s:0:"";}s:16:"tirednessalchemy";a:3:{s:4:"name";s:22:"Alchimie de la Fatigue";s:1:"p";s:23:"Alchimies de la Fatigue";s:11:"description";s:0:"";}s:8:"tracking";a:3:{s:4:"name";s:7:"Pistage";s:1:"p";s:8:"Pistages";s:11:"description";s:0:"";}s:22:"tradingcenterknowledge";a:3:{s:4:"name";s:30:"Connaissance du Centre de Troc";s:1:"p";s:31:"Connaissances du Centre de Troc";s:11:"description";s:0:"";}s:15:"trainingagility";a:3:{s:4:"name";s:27:"Entraînement de l\'Agilité";s:1:"p";s:28:"Entraînements de l\'Agilité";s:11:"description";s:0:"";}s:14:"traininganimal";a:3:{s:4:"name";s:29:"Entraînement sur les Animaux";s:1:"p";s:30:"Entraînements sur les Animaux";s:11:"description";s:0:"";}s:16:"trainingcharisme";a:3:{s:4:"name";s:25:"Entraînement du Charisme";s:1:"p";s:26:"Entraînements du Charisme";s:11:"description";s:0:"";}s:20:"trainingconstitution";a:3:{s:4:"name";s:32:"Entraînement de la Constitution";s:1:"p";s:33:"Entraînements de la Constitution";s:11:"description";s:0:"";}s:21:"trainingdiseaseresist";a:3:{s:4:"name";s:41:"Entraînement de Résistance aux Maladies";s:1:"p";s:42:"Entraînements de Résistance aux Maladies";s:11:"description";s:0:"";}s:16:"trainingempathie";a:3:{s:4:"name";s:27:"Entraînement de l\'Empathie";s:1:"p";s:28:"Entraînements de l\'Empathie";s:11:"description";s:0:"";}s:18:"trainingfearresist";a:3:{s:4:"name";s:39:"Entraînement de Résistance à la Peur";s:1:"p";s:40:"Entraînements de Résistance à la Peur";s:11:"description";s:0:"";}s:10:"traininghp";a:3:{s:4:"name";s:31:"Entraînement des Points de Vie";s:1:"p";s:32:"Entraînements des Points de Vie";s:11:"description";s:0:"";}s:11:"traininghp2";a:3:{s:4:"name";s:33:"Entraînement des Points de Vie 2";s:1:"p";s:34:"Entraînements des Points de Vie 2";s:11:"description";s:0:"";}s:11:"traininghp3";a:3:{s:4:"name";s:33:"Entraînement des Points de Vie 3";s:1:"p";s:34:"Entraînements des Points de Vie 3";s:11:"description";s:0:"";}s:11:"traininghp4";a:3:{s:4:"name";s:33:"Entraînement des Points de Vie 4";s:1:"p";s:34:"Entraînements des Points de Vie 4";s:11:"description";s:0:"";}s:20:"trainingimpactresist";a:3:{s:4:"name";s:36:"Entraînement de Résistance Impacts";s:1:"p";s:37:"Entraînements de Résistance Impacts";s:11:"description";s:0:"";}s:20:"trainingintelligence";a:3:{s:4:"name";s:31:"Entraînement de l\'Intelligence";s:1:"p";s:32:"Entraînements de l\'Intelligence";s:11:"description";s:0:"";}s:19:"trainingmagicresist";a:3:{s:4:"name";s:40:"Entraînement de Résistance à la Magie";s:1:"p";s:41:"Entraînements de Résistance à la Magie";s:11:"description";s:0:"";}s:18:"trainingperception";a:3:{s:4:"name";s:30:"Entraînement de la Perception";s:1:"p";s:31:"Entraînements de la Perception";s:11:"description";s:0:"";}s:12:"trainingseve";a:3:{s:4:"name";s:25:"Entraînement de la Sève";s:1:"p";s:26:"Entraînements de la Sève";s:11:"description";s:0:"";}s:13:"trainingseve2";a:3:{s:4:"name";s:27:"Entraînement de la Sève 2";s:1:"p";s:28:"Entraînements de la Sève 2";s:11:"description";s:0:"";}s:13:"trainingseve3";a:3:{s:4:"name";s:27:"Entraînement de la Sève 3";s:1:"p";s:28:"Entraînements de la Sève 3";s:11:"description";s:0:"";}s:13:"trainingseve4";a:3:{s:4:"name";s:27:"Entraînement de la Sève 4";s:1:"p";s:28:"Entraînements de la Sève 4";s:11:"description";s:0:"";}s:15:"trainingstamina";a:3:{s:4:"name";s:28:"Entraînement de l\'Endurance";s:1:"p";s:29:"Entraînements de l\'Endurance";s:11:"description";s:0:"";}s:16:"trainingstamina2";a:3:{s:4:"name";s:30:"Entraînement de l\'Endurance 2";s:1:"p";s:31:"Entraînements de l\'Endurance 2";s:11:"description";s:0:"";}s:16:"trainingstamina3";a:3:{s:4:"name";s:30:"Entraînement de l\'Endurance 3";s:1:"p";s:31:"Entraînements de l\'Endurance 3";s:11:"description";s:0:"";}s:16:"trainingstamina4";a:3:{s:4:"name";s:30:"Entraînement de l\'Endurance 4";s:1:"p";s:31:"Entraînements de l\'Endurance 4";s:11:"description";s:0:"";}s:16:"trainingstrength";a:3:{s:4:"name";s:25:"Entraînement de la Force";s:1:"p";s:26:"Entraînements de la Force";s:11:"description";s:0:"";}s:20:"trainingtechnoresist";a:3:{s:4:"name";s:39:"Entraînement de Résistance aux Techno";s:1:"p";s:40:"Entraînements de Résistance aux Techno";s:11:"description";s:0:"";}s:20:"trainingwellbalanced";a:3:{s:4:"name";s:30:"Entraînement du Bon Equilibre";s:1:"p";s:31:"Entraînements du Bon Equilibre";s:11:"description";s:0:"";}s:12:"trainingwill";a:3:{s:4:"name";s:28:"Entraînement de la Volonté";s:1:"p";s:29:"Entraînements de la Volonté";s:11:"description";s:0:"";}s:6:"tryker";a:3:{s:4:"name";s:6:"Tryker";s:1:"p";s:7:"Trykers";s:11:"description";s:0:"";}s:11:"trykergoods";a:3:{s:4:"name";s:12:"Biens Tryker";s:1:"p";s:13:"Biens Trykers";s:11:"description";s:0:"";}s:13:"trykermystery";a:3:{s:4:"name";s:15:"Mystère Tryker";s:1:"p";s:17:"Mystères Trykers";s:11:"description";s:0:"";}s:15:"twohandedcombat";a:3:{s:4:"name";s:12:"Combat à 2M";s:1:"p";s:13:"Combats à 2M";s:11:"description";s:0:"";}s:13:"twohandssword";a:3:{s:4:"name";s:11:"Epée à 2M";s:1:"p";s:12:"Epées à 2M";s:11:"description";s:0:"";}s:17:"underwaterextract";a:3:{s:4:"name";s:22:"Extraction Sous-Marine";s:1:"p";s:24:"Extractions Sous-Marines";s:11:"description";s:0:"";}s:15:"visualwithering";a:3:{s:4:"name";s:25:"Affaiblissement de la Vue";s:1:"p";s:26:"Affaiblissements de la Vue";s:11:"description";s:0:"";}s:10:"vitalburst";a:3:{s:4:"name";s:16:"Explosion Vitale";s:1:"p";s:18:"Explosions Vitales";s:11:"description";s:0:"";}s:11:"warriorgift";a:3:{s:4:"name";s:12:"Don Guerrier";s:1:"p";s:14:"Dons Guerriers";s:11:"description";s:0:"";}s:14:"weaponscutting";a:3:{s:4:"name";s:15:"Coupe des Armes";s:1:"p";s:16:"Coupes des Armes";s:11:"description";s:0:"";}s:9:"windarmor";a:3:{s:4:"name";s:16:"Armure des Vents";s:1:"p";s:17:"Armures des Vents";s:11:"description";s:0:"";}s:8:"windgift";a:3:{s:4:"name";s:11:"Don de Vent";s:1:"p";s:13:"Dons de Vents";s:11:"description";s:0:"";}s:9:"windshear";a:3:{s:4:"name";s:17:"Puissance du Vent";s:1:"p";s:18:"Puissances du Vent";s:11:"description";s:0:"";}s:5:"zorai";a:3:{s:4:"name";s:6:"Zoraï";s:1:"p";s:7:"Zoraïs";s:11:"description";s:0:"";}s:10:"zoraigoods";a:3:{s:4:"name";s:12:"Biens Zoraï";s:1:"p";s:13:"Biens Zoraïs";s:11:"description";s:0:"";}s:12:"zoraimystery";a:3:{s:4:"name";s:15:"Mystère Zoraï";s:1:"p";s:17:"Mystères Zoraïs";s:11:"description";s:0:"";}s:21:"trainingimpactresist2";a:3:{s:4:"name";s:38:"Entraînement de Résistance Impacts 2";s:1:"p";s:39:"Entraînements de Résistance Impacts 2";s:11:"description";s:0:"";}s:21:"trainingimpactresist3";a:3:{s:4:"name";s:38:"Entraînement de Résistance Impacts 3";s:1:"p";s:39:"Entraînements de Résistance Impacts 3";s:11:"description";s:0:"";}s:20:"trainingmagicresist2";a:3:{s:4:"name";s:38:"Entraînement de Résistance Impacts 2";s:1:"p";s:39:"Entraînements de Résistance Impacts 2";s:11:"description";s:0:"";}s:20:"trainingmagicresist3";a:3:{s:4:"name";s:38:"Entraînement de Résistance Impacts 3";s:1:"p";s:39:"Entraînements de Résistance Impacts 3";s:11:"description";s:0:"";}s:20:"makeenchantedweapons";a:3:{s:4:"name";s:31:"Fabrication d\'Armes Enchantées";s:1:"p";s:32:"Fabrications d\'Armes Enchantées";s:11:"description";s:0:"";}s:19:"makeenchantedarmors";a:3:{s:4:"name";s:33:"Fabrication d\'Armures Enchantées";s:1:"p";s:34:"Fabrications d\'Armures Enchantées";s:11:"description";s:0:"";}s:19:"makevampiricweapons";a:3:{s:4:"name";s:31:"Fabrication d\'Armes Vampiriques";s:1:"p";s:32:"Fabrications d\'Armes Vampiriques";s:11:"description";s:0:"";}s:39:"makeenchantedfloralpatternlivingclothes";a:3:{s:4:"name";s:35:"Fabr. de Vêtements Floraux Vivants";s:1:"p";s:35:"Fabr. de Vêtements Floraux Vivants";s:11:"description";s:0:"";}s:20:"makedeadlyharpoongun";a:3:{s:4:"name";s:33:"Fabr. d\'un Pistolet-Harpon Mortel";s:1:"p";s:34:"Fabr. de Pistolets-Harpons Mortels";s:11:"description";s:0:"";}s:17:"makehuntingarmors";a:3:{s:4:"name";s:30:"Fabrication d\'Armure de Chasse";s:1:"p";s:32:"Fabrications d\'Armures de Chasse";s:11:"description";s:0:"";}s:17:"makeritualclothes";a:3:{s:4:"name";s:33:"Fabrication de Vêtements Rituels";s:1:"p";s:34:"Fabrications de Vêtements Rituels";s:11:"description";s:0:"";}s:16:"makeritualjewels";a:3:{s:4:"name";s:29:"Fabrication de Bijoux Rituels";s:1:"p";s:30:"Fabrications de Bijoux Rituels";s:11:"description";s:0:"";}s:2:"sc";a:3:{s:4:"name";s:9:"Artisanat";s:1:"p";s:9:"Artisanat";s:11:"description";s:9:"Artisanat";}s:2:"sm";a:3:{s:4:"name";s:5:"Magie";s:1:"p";s:5:"Magie";s:11:"description";s:5:"Magie";}s:3:"smt";a:3:{s:4:"name";s:14:"Magie d\'Impact";s:1:"p";s:16:"Magies d\'Impacts";s:11:"description";s:13:"Magie Ciblée";}s:4:"smtm";a:3:{s:4:"name";s:16:"Magie de Missile";s:1:"p";s:18:"Magies de Missiles";s:11:"description";s:24:"Magie de Missile Ciblée";}s:5:"smtmr";a:3:{s:4:"name";s:18:"Magie Fondamentale";s:1:"p";s:20:"Magies Fondamentales";s:11:"description";s:39:"Magie de Missile Ciblée - Fondamentale";}s:5:"smtmg";a:3:{s:4:"name";s:15:"Magie Atysienne";s:1:"p";s:17:"Magies Atysiennes";s:11:"description";s:36:"Magie de Missile Ciblée - Atysienne";}s:5:"smtmb";a:3:{s:4:"name";s:14:"Magie Céleste";s:1:"p";s:16:"Magies Célestes";s:11:"description";s:35:"Magie de Missile Ciblée - Céleste";}s:4:"smto";a:3:{s:4:"name";s:15:"Magie Offensive";s:1:"p";s:17:"Magies Offensives";s:11:"description";s:23:"Magie Offensive Ciblée";}s:5:"smtod";a:3:{s:4:"name";s:14:"Magie Physique";s:1:"p";s:16:"Magies Physiques";s:11:"description";s:40:"Magie Offensive Ciblée sur les Dégâts";}s:6:"smtodp";a:3:{s:4:"name";s:16:"Magie Perforante";s:1:"p";s:18:"Magies Perforantes";s:11:"description";s:53:"Magie Offensive Ciblée sur les Dégâts - Perforante";}s:6:"smtodb";a:3:{s:4:"name";s:17:"Magie Contondante";s:1:"p";s:19:"Magies Contondantes";s:11:"description";s:53:"Magie Offensive Ciblée sur les Dégâts - Contondant";}s:6:"smtods";a:3:{s:4:"name";s:16:"Magie Tranchante";s:1:"p";s:18:"Magies Tranchantes";s:11:"description";s:53:"Magie Offensive Ciblée sur les Dégâts - Tranchante";}s:5:"smtoe";a:3:{s:4:"name";s:18:"Magie Elémentaire";s:1:"p";s:20:"Magies Elémentaires";s:11:"description";s:50:"Magie Offensive Ciblée sur les Dégâts Spéciaux";}s:6:"smtoec";a:3:{s:4:"name";s:14:"Magie du Froid";s:1:"p";s:15:"Magies du Froid";s:11:"description";s:58:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Froid";}s:6:"smtoea";a:3:{s:4:"name";s:16:"Magie de l\'Acide";s:1:"p";s:17:"Magies de l\'Acide";s:11:"description";s:58:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Acide";}s:6:"smtoer";a:3:{s:4:"name";s:22:"Magie de la Pourriture";s:1:"p";s:23:"Magies de la Pourriture";s:11:"description";s:63:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Pourriture";}s:6:"smtoef";a:3:{s:4:"name";s:12:"Magie du Feu";s:1:"p";s:13:"Magies du Feu";s:11:"description";s:56:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Feu";}s:6:"smtoes";a:3:{s:4:"name";s:23:"Magie des Ondes de Choc";s:1:"p";s:24:"Magies des Ondes de Choc";s:11:"description";s:66:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Ondes de choc";}s:6:"smtoep";a:3:{s:4:"name";s:15:"Magie du Poison";s:1:"p";s:16:"Magies du Poison";s:11:"description";s:59:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Poison";}s:6:"smtoee";a:3:{s:4:"name";s:16:"Magie Electrique";s:1:"p";s:18:"Magies Electriques";s:11:"description";s:65:"Magie Offensive Ciblée sur les Dégâts Spéciaux - Electricité";}s:4:"smtc";a:3:{s:4:"name";s:14:"Magie Curative";s:1:"p";s:16:"Magies Curatives";s:11:"description";s:22:"Magie Curative Ciblée";}s:5:"smtch";a:3:{s:4:"name";s:18:"Magie de Guérison";s:1:"p";s:19:"Magies de Guérison";s:11:"description";s:39:"Magie Curative Ciblée sur la Guérison";}s:6:"smtchp";a:3:{s:4:"name";s:28:"Magie de Guérison de la Vie";s:1:"p";s:29:"Magies de Guérison de la Vie";s:11:"description";s:45:"Magie Curative Ciblée sur la Guérison - Vie";}s:6:"smtcht";a:3:{s:4:"name";s:28:"Magie de Guérison de la Sta";s:1:"p";s:29:"Magies de Guérison de la Sta";s:11:"description";s:45:"Magie Curative Ciblée sur la Guérison - Sta";}s:6:"smtcha";a:3:{s:4:"name";s:30:"Magie de Guérison de la Sève";s:1:"p";s:31:"Magies de Guérison de la Sève";s:11:"description";s:47:"Magie Curative Ciblée sur la Guérison - Sève";}s:6:"smtchc";a:3:{s:4:"name";s:38:"Magie de Guérison de la Concentration";s:1:"p";s:39:"Magies de Guérison de la Concentration";s:11:"description";s:55:"Magie Curative Ciblée sur la Guérison - Concentration";}s:5:"smtcb";a:3:{s:4:"name";s:15:"Rupture de Lien";s:1:"p";s:17:"Ruptures de Liens";s:11:"description";s:45:"Magie Curative Ciblée sur la Rupture de Lien";}s:6:"smtcbd";a:3:{s:4:"name";s:26:"Dégât de Rupture de Lien";s:1:"p";s:29:"Dégâts de Ruptures de Liens";s:11:"description";s:56:"Magie Curative Ciblée sur la Rupture de Lien - Dégâts";}s:6:"smtcbs";a:3:{s:4:"name";s:26:"Maladie de Rupture de Lien";s:1:"p";s:29:"Maladies de Ruptures de Liens";s:11:"description";s:55:"Magie Curative Ciblée sur la Rupture de Lien - Maladie";}s:6:"smtcbc";a:3:{s:4:"name";s:31:"Malédiction de Rupture de Lien";s:1:"p";s:34:"Malédictions de Ruptures de Liens";s:11:"description";s:60:"Magie Curative Ciblée sur la Rupture de Lien - Malédiction";}s:3:"sml";a:3:{s:4:"name";s:13:"Lien de Magie";s:1:"p";s:15:"Liens de Magies";s:11:"description";s:13:"Lien de Magie";}s:4:"smlm";a:3:{s:4:"name";s:15:"Lien de Missile";s:1:"p";s:17:"Liens de Missiles";s:11:"description";s:34:"Lien de Magie relatif aux Missiles";}s:5:"smlmr";a:3:{s:4:"name";s:16:"Lien Fondamental";s:1:"p";s:18:"Liens Fondamentaux";s:11:"description";s:48:"Lien de Magie relatif aux Missiles - Fondamental";}s:5:"smlmg";a:3:{s:4:"name";s:12:"Lien Atysien";s:1:"p";s:14:"Liens Atysiens";s:11:"description";s:44:"Lien de Magie relatif aux Missiles - Atysien";}s:5:"smlmb";a:3:{s:4:"name";s:13:"Lien Céleste";s:1:"p";s:15:"Liens Célestes";s:11:"description";s:45:"Lien de Magie relatif aux Missiles - Céleste";}s:4:"smlo";a:3:{s:4:"name";s:13:"Lien Offensif";s:1:"p";s:15:"Liens Offensifs";s:11:"description";s:23:"Lien de Magie Offensive";}s:5:"smlos";a:3:{s:4:"name";s:15:"Lien de Maladie";s:1:"p";s:17:"Liens de Maladies";s:11:"description";s:44:"Lien de Magie Offensive relatif aux Maladies";}s:6:"smlosm";a:3:{s:4:"name";s:13:"Lien de Folie";s:1:"p";s:14:"Liens de Folie";s:11:"description";s:52:"Lien de Magie Offensive relatif aux Maladies - folie";}s:6:"smloss";a:3:{s:4:"name";s:32:"Lien de Diminution de la Vitesse";s:1:"p";s:33:"Liens de Diminution de la Vitesse";s:11:"description";s:71:"Lien de Magie Offensive relatif aux Maladies - diminution de la vitesse";}s:6:"smlosr";a:3:{s:4:"name";s:36:"Lien de Diminution de la Résistance";s:1:"p";s:37:"Liens de Diminution de la Résistance";s:11:"description";s:75:"Lien de Magie Offensive relatif aux Maladies - diminution de la résistance";}s:6:"smlosk";a:3:{s:4:"name";s:35:"Lien de Diminution des Compétences";s:1:"p";s:36:"Liens de Diminution des Compétences";s:11:"description";s:74:"Lien de Magie Offensive relatif aux Maladies - diminution des compétences";}s:5:"smloc";a:3:{s:4:"name";s:20:"Lien de Malédiction";s:1:"p";s:22:"Liens de Malédictions";s:11:"description";s:49:"Lien de Magie Offensive relatif aux Malédictions";}s:6:"smloci";a:3:{s:4:"name";s:18:"Lien d\'Incapacité";s:1:"p";s:19:"Liens d\'Incapacité";s:11:"description";s:63:"Lien de Magie Offensive relatif aux Malédictions - incapacité";}s:6:"smlocm";a:3:{s:4:"name";s:28:"Lien de Perturbation Mentale";s:1:"p";s:29:"Liens de Perturbation Mentale";s:11:"description";s:72:"Lien de Magie Offensive relatif aux Malédictions - perturbation mentale";}s:6:"smlocs";a:3:{s:4:"name";s:17:"Lien de Malchance";s:1:"p";s:18:"Liens de Malchance";s:11:"description";s:61:"Lien de Magie Offensive relatif aux Malédictions - malchance";}s:6:"smloch";a:3:{s:4:"name";s:13:"Lien de Haine";s:1:"p";s:14:"Liens de Haine";s:11:"description";s:57:"Lien de Magie Offensive relatif aux Malédictions - haine";}s:5:"smlod";a:3:{s:4:"name";s:13:"Lien Physique";s:1:"p";s:15:"Liens Physiques";s:11:"description";s:44:"Lien de Magie Offensive relatif aux Dégâts";}s:6:"smlodp";a:3:{s:4:"name";s:14:"Lien Perforant";s:1:"p";s:16:"Liens Perforants";s:11:"description";s:56:"Lien de Magie Offensive relatif aux Dégâts - Perforant";}s:6:"smlodb";a:3:{s:4:"name";s:15:"Lien Contondant";s:1:"p";s:17:"Liens Contondants";s:11:"description";s:57:"Lien de magie offensive relatif aux dégâts - contondant";}s:6:"smlods";a:3:{s:4:"name";s:14:"Lien Tranchant";s:1:"p";s:16:"Liens Tranchants";s:11:"description";s:56:"Lien de Magie Offensive relatif aux Dégâts - Tranchant";}s:5:"smloe";a:3:{s:4:"name";s:17:"Lien Elémentaire";s:1:"p";s:19:"Liens Elémentaires";s:11:"description";s:54:"Lien de Magie Offensive relatif aux Dégâts Spéciaux";}s:6:"smloec";a:3:{s:4:"name";s:13:"Lien du Froid";s:1:"p";s:14:"Liens du Froid";s:11:"description";s:62:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Froid";}s:6:"smloea";a:3:{s:4:"name";s:12:"Lien d\'Acide";s:1:"p";s:13:"Liens d\'Acide";s:11:"description";s:62:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Acide";}s:6:"smloer";a:3:{s:4:"name";s:18:"Lien de Pourriture";s:1:"p";s:19:"Liens de Pourriture";s:11:"description";s:67:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Pourriture";}s:6:"smloef";a:3:{s:4:"name";s:11:"Lien de Feu";s:1:"p";s:12:"Liens de Feu";s:11:"description";s:60:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Feu";}s:6:"smloes";a:3:{s:4:"name";s:20:"Lien d\'Ondes de Choc";s:1:"p";s:21:"Liens d\'Ondes de Choc";s:11:"description";s:70:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Ondes de choc";}s:6:"smloep";a:3:{s:4:"name";s:14:"Lien de Poison";s:1:"p";s:15:"Liens de Poison";s:11:"description";s:63:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Poison";}s:6:"smloee";a:3:{s:4:"name";s:15:"Lien Electrique";s:1:"p";s:17:"Liens Electriques";s:11:"description";s:69:"Lien de Magie Offensive relatif aux Dégâts Spéciaux - Electricité";}s:4:"smlc";a:3:{s:4:"name";s:12:"Lien Curatif";s:1:"p";s:14:"Liens Curatifs";s:11:"description";s:22:"Lien de Magie Curative";}s:5:"smlch";a:3:{s:4:"name";s:17:"Lien de Guérison";s:1:"p";s:18:"Liens de Guérison";s:11:"description";s:45:"Lien de Magie Curative relatif aux Guérisons";}s:6:"smlchp";a:3:{s:4:"name";s:27:"Lien de Guérison de la Vie";s:1:"p";s:28:"Liens de Guérison de la Vie";s:11:"description";s:51:"Lien de Magie Curative relatif aux Guérisons - Vie";}s:6:"smlcht";a:3:{s:4:"name";s:27:"Lien de Guérison de la Sta";s:1:"p";s:28:"Liens de Guérison de la Sta";s:11:"description";s:51:"Lien de Magie Curative relatif aux Guérisons - Sta";}s:6:"smlcha";a:3:{s:4:"name";s:29:"Lien de Guérison de la Sève";s:1:"p";s:30:"Liens de Guérison de la Sève";s:11:"description";s:53:"Lien de Magie Curative relatif aux Guérisons - Sève";}s:6:"smlchc";a:3:{s:4:"name";s:37:"Lien de Guérison de la Concentration";s:1:"p";s:38:"Liens de Guérison de la Concentration";s:11:"description";s:61:"Lien de Magie Curative relatif aux Guérisons - Concentration";}s:3:"sma";a:3:{s:4:"name";s:14:"Magie Autonome";s:1:"p";s:16:"Magies Autonomes";s:11:"description";s:14:"Magie Autonome";}s:4:"smae";a:3:{s:4:"name";s:20:"Magie d\'Enchantement";s:1:"p";s:22:"Magies d\'Enchantements";s:11:"description";s:20:"Magie d\'Enchantement";}s:5:"smaes";a:3:{s:4:"name";s:21:"Enchantement de Sorts";s:1:"p";s:30:"Magies d\'Enchantements - Sorts";s:11:"description";s:27:"Magie d\'Enchantement - Sort";}s:5:"smaeb";a:3:{s:4:"name";s:18:"Enchantement Bonus";s:1:"p";s:30:"Magies d\'Enchantements - Bonus";s:11:"description";s:28:"Magie d\'Enchantement - Bonus";}s:4:"smap";a:3:{s:4:"name";s:29:"Magie de Création de Potions";s:1:"p";s:33:"Magies de Fabrications de Potions";s:11:"description";s:31:"Magie de Fabrication de Potions";}s:2:"sd";a:3:{s:4:"name";s:13:"Auto-Défense";s:1:"p";s:14:"Auto-Défenses";s:11:"description";s:13:"Auto-Défense";}s:3:"sda";a:3:{s:4:"name";s:13:"Porter Armure";s:1:"p";s:13:"Porter Armure";s:11:"description";s:13:"Porter Armure";}s:4:"sdal";a:3:{s:4:"name";s:22:"Porter Armure Légère";s:1:"p";s:22:"Porter Armure Légère";s:11:"description";s:22:"Porter Armure Légère";}s:5:"sdalf";a:3:{s:4:"name";s:28:"Porter Armure Légère Fyros";s:1:"p";s:28:"Porter Armure Légère Fyros";s:11:"description";s:28:"Porter Armure Légère Fyros";}s:6:"sdalfa";a:3:{s:4:"name";s:38:"Expert en Port d\'Armure Légère Fyros";s:1:"p";s:39:"Experts en Port d\'Armure Légère Fyros";s:11:"description";s:38:"Expert en Port d\'Armure Légère Fyros";}s:7:"sdalfae";a:3:{s:4:"name";s:39:"Maître en Port d\'Armure Légère Fyros";s:1:"p";s:40:"Maîtres en Port d\'Armure Légère Fyros";s:11:"description";s:39:"Maître en Port d\'Armure Légère Fyros";}s:5:"sdalm";a:3:{s:4:"name";s:28:"Porter Armure Légère Matis";s:1:"p";s:28:"Porter Armure Légère Matis";s:11:"description";s:28:"Porter Armure Légère Matis";}s:6:"sdalma";a:3:{s:4:"name";s:38:"Expert en Port d\'Armure Légère Matis";s:1:"p";s:39:"Experts en Port d\'Armure Légère Matis";s:11:"description";s:38:"Expert en Port d\'Armure Légère Matis";}s:7:"sdalmae";a:3:{s:4:"name";s:39:"Maître en Port d\'Armure Légère Matis";s:1:"p";s:40:"Maîtres en Port d\'Armure Légère Matis";s:11:"description";s:39:"Maître en Port d\'Armure Légère Matis";}s:5:"sdalt";a:3:{s:4:"name";s:29:"Porter Armure Légère Tryker";s:1:"p";s:29:"Porter Armure Légère Tryker";s:11:"description";s:29:"Porter Armure Légère Tryker";}s:6:"sdalta";a:3:{s:4:"name";s:39:"Expert en Port d\'Armure Légère Tryker";s:1:"p";s:40:"Experts en Port d\'Armure Légère Tryker";s:11:"description";s:39:"Expert en Port d\'Armure Légère Tryker";}s:7:"sdaltae";a:3:{s:4:"name";s:40:"Maître en Port d\'Armure Légère Tryker";s:1:"p";s:41:"Maîtres en Port d\'Armure Légère Tryker";s:11:"description";s:40:"Maître en Port d\'Armure Légère Tryker";}s:5:"sdalz";a:3:{s:4:"name";s:29:"Porter Armure Légère Zoraï";s:1:"p";s:29:"Porter Armure Légère Zoraï";s:11:"description";s:29:"Porter Armure Légère Zoraï";}s:6:"sdalza";a:3:{s:4:"name";s:39:"Expert en Port d\'Armure Légère Zoraï";s:1:"p";s:40:"Experts en Port d\'Armure Légère Zoraï";s:11:"description";s:39:"Expert en Port d\'Armure Légère Zoraï";}s:7:"sdalzae";a:3:{s:4:"name";s:40:"Maître en Port d\'Armure Légère Zoraï";s:1:"p";s:41:"Maîtres en Port d\'Armure Légère Zoraï";s:11:"description";s:40:"Maître en Port d\'Armure Légère Zoraï";}s:4:"sdam";a:3:{s:4:"name";s:21:"Porter Armure Moyenne";s:1:"p";s:21:"Porter Armure Moyenne";s:11:"description";s:21:"Porter Armure Moyenne";}s:5:"sdamf";a:3:{s:4:"name";s:27:"Porter Armure Moyenne Fyros";s:1:"p";s:27:"Porter Armure Moyenne Fyros";s:11:"description";s:27:"Porter Armure Moyenne Fyros";}s:6:"sdamfa";a:3:{s:4:"name";s:37:"Expert en Port d\'Armure Moyenne Fyros";s:1:"p";s:38:"Experts en Port d\'Armure Moyenne Fyros";s:11:"description";s:37:"Expert en Port d\'Armure Moyenne Fyros";}s:7:"sdamfae";a:3:{s:4:"name";s:38:"Maître en Port d\'Armure Moyenne Fyros";s:1:"p";s:39:"Maîtres en Port d\'Armure Moyenne Fyros";s:11:"description";s:38:"Maître en Port d\'Armure Moyenne Fyros";}s:5:"sdamm";a:3:{s:4:"name";s:27:"Porter Armure Moyenne Matis";s:1:"p";s:27:"Porter Armure Moyenne Matis";s:11:"description";s:27:"Porter Armure Moyenne Matis";}s:6:"sdamma";a:3:{s:4:"name";s:37:"Expert en Port d\'Armure Moyenne Matis";s:1:"p";s:38:"Experts en Port d\'Armure Moyenne Matis";s:11:"description";s:37:"Expert en Port d\'Armure Moyenne Matis";}s:7:"sdammae";a:3:{s:4:"name";s:38:"Maître en Port d\'Armure Moyenne Matis";s:1:"p";s:39:"Maîtres en Port d\'Armure Moyenne Matis";s:11:"description";s:38:"Maître en Port d\'Armure Moyenne Matis";}s:5:"sdamt";a:3:{s:4:"name";s:28:"Porter Armure Moyenne Tryker";s:1:"p";s:28:"Porter Armure Moyenne Tryker";s:11:"description";s:28:"Porter Armure Moyenne Tryker";}s:6:"sdamta";a:3:{s:4:"name";s:38:"Expert en Port d\'Armure Moyenne Tryker";s:1:"p";s:39:"Experts en Port d\'Armure Moyenne Tryker";s:11:"description";s:38:"Expert en Port d\'Armure Moyenne Tryker";}s:7:"sdamtae";a:3:{s:4:"name";s:39:"Maître en Port d\'Armure Moyenne Tryker";s:1:"p";s:40:"Maîtres en Port d\'Armure Moyenne Tryker";s:11:"description";s:39:"Maître en Port d\'Armure Moyenne Tryker";}s:5:"sdamz";a:3:{s:4:"name";s:28:"Porter Armure Moyenne Zoraï";s:1:"p";s:28:"Porter Armure Moyenne Zoraï";s:11:"description";s:28:"Porter Armure Moyenne Zoraï";}s:6:"sdamza";a:3:{s:4:"name";s:38:"Expert en Port d\'Armure Moyenne Zoraï";s:1:"p";s:39:"Experts en Port d\'Armure Moyenne Zoraï";s:11:"description";s:38:"Expert en Port d\'Armure Moyenne Zoraï";}s:7:"sdamzae";a:3:{s:4:"name";s:39:"Maître en Port d\'Armure Moyenne Zoraï";s:1:"p";s:40:"Maîtres en Port d\'Armure Moyenne Zoraï";s:11:"description";s:39:"Maître en Port d\'Armure Moyenne Zoraï";}s:4:"sdah";a:3:{s:4:"name";s:20:"Porter Armure Lourde";s:1:"p";s:20:"Porter Armure Lourde";s:11:"description";s:20:"Porter Armure Lourde";}s:5:"sdahf";a:3:{s:4:"name";s:26:"Porter Armure Lourde Fyros";s:1:"p";s:26:"Porter Armure Lourde Fyros";s:11:"description";s:26:"Porter Armure Lourde Fyros";}s:6:"sdahfa";a:3:{s:4:"name";s:36:"Expert en Port d\'Armure Lourde Fyros";s:1:"p";s:37:"Experts en Port d\'Armure Lourde Fyros";s:11:"description";s:36:"Expert en Port d\'Armure Lourde Fyros";}s:7:"sdahfae";a:3:{s:4:"name";s:37:"Maître en Port d\'Armure Lourde Fyros";s:1:"p";s:38:"Maîtres en Port d\'Armure Lourde Fyros";s:11:"description";s:37:"Maître en Port d\'Armure Lourde Fyros";}s:5:"sdahm";a:3:{s:4:"name";s:26:"Porter Armure Lourde Matis";s:1:"p";s:26:"Porter Armure Lourde Matis";s:11:"description";s:26:"Porter Armure Lourde Matis";}s:6:"sdahma";a:3:{s:4:"name";s:36:"Expert en Port d\'Armure Lourde Matis";s:1:"p";s:37:"Experts en Port d\'Armure Lourde Matis";s:11:"description";s:36:"Expert en Port d\'Armure Lourde Matis";}s:7:"sdahmae";a:3:{s:4:"name";s:37:"Maître en Port d\'Armure Lourde Matis";s:1:"p";s:38:"Maîtres en Port d\'Armure Lourde Matis";s:11:"description";s:37:"Maître en Port d\'Armure Lourde Matis";}s:5:"sdaht";a:3:{s:4:"name";s:27:"Porter Armure Lourde Tryker";s:1:"p";s:27:"Porter Armure Lourde Tryker";s:11:"description";s:27:"Porter Armure Lourde Tryker";}s:6:"sdahta";a:3:{s:4:"name";s:37:"Expert en Port d\'Armure Lourde Tryker";s:1:"p";s:38:"Experts en Port d\'Armure Lourde Tryker";s:11:"description";s:37:"Expert en Port d\'Armure Lourde Tryker";}s:7:"sdahtae";a:3:{s:4:"name";s:38:"Maître en Port d\'Armure Lourde Tryker";s:1:"p";s:39:"Maîtres en Port d\'Armure Lourde Tryker";s:11:"description";s:38:"Maître en Port d\'Armure Lourde Tryker";}s:5:"sdahz";a:3:{s:4:"name";s:27:"Porter Armure Lourde Zoraï";s:1:"p";s:27:"Porter Armure Lourde Zoraï";s:11:"description";s:27:"Porter Armure Lourde Zoraï";}s:6:"sdahza";a:3:{s:4:"name";s:37:"Expert en Port d\'Armure Lourde Zoraï";s:1:"p";s:38:"Experts en Port d\'Armure Lourde Zoraï";s:11:"description";s:37:"Expert en Port d\'Armure Lourde Zoraï";}s:7:"sdahzae";a:3:{s:4:"name";s:38:"Maître en Port d\'Armure Lourde Zoraï";s:1:"p";s:39:"Maîtres en Port d\'Armure Lourde Zoraï";s:11:"description";s:38:"Maître en Port d\'Armure Lourde Zoraï";}s:3:"sds";a:3:{s:4:"name";s:16:"Manier Boucliers";s:1:"p";s:16:"Manier Boucliers";s:11:"description";s:16:"Manier Boucliers";}s:4:"sdsb";a:3:{s:4:"name";s:15:"Manier Rondache";s:1:"p";s:15:"Manier Rondache";s:11:"description";s:15:"Manier Rondache";}s:5:"sdsbf";a:3:{s:4:"name";s:21:"Manier Rondache Fyros";s:1:"p";s:21:"Manier Rondache Fyros";s:11:"description";s:21:"Manier Rondache Fyros";}s:6:"sdsbfa";a:3:{s:4:"name";s:37:"Expert en Maniement de Rondache Fyros";s:1:"p";s:38:"Experts en Maniement de Rondache Fyros";s:11:"description";s:37:"Expert en Maniement de Rondache Fyros";}s:7:"sdsbfae";a:3:{s:4:"name";s:38:"Maître en Maniement de Rondache Fyros";s:1:"p";s:39:"Maîtres en Maniement de Rondache Fyros";s:11:"description";s:38:"Maître en Maniement de Rondache Fyros";}s:5:"sdsbm";a:3:{s:4:"name";s:21:"Manier Rondache Matis";s:1:"p";s:21:"Manier Rondache Matis";s:11:"description";s:21:"Manier Rondache Matis";}s:6:"sdsbma";a:3:{s:4:"name";s:37:"Expert en Maniement de Rondache Matis";s:1:"p";s:38:"Experts en Maniement de Rondache Matis";s:11:"description";s:37:"Expert en Maniement de Rondache Matis";}s:7:"sdsbmae";a:3:{s:4:"name";s:38:"Maître en Maniement de Rondache Matis";s:1:"p";s:39:"Maîtres en Maniement de Rondache Matis";s:11:"description";s:38:"Maître en Maniement de Rondache Matis";}s:5:"sdsbt";a:3:{s:4:"name";s:22:"Manier Rondache Tryker";s:1:"p";s:22:"Manier Rondache Tryker";s:11:"description";s:22:"Manier Rondache Tryker";}s:6:"sdsbta";a:3:{s:4:"name";s:38:"Expert en Maniement de Rondache Tryker";s:1:"p";s:39:"Experts en Maniement de Rondache Tryker";s:11:"description";s:38:"Expert en Maniement de Rondache Tryker";}s:7:"sdsbtae";a:3:{s:4:"name";s:39:"Maître en Maniement de Rondache Tryker";s:1:"p";s:40:"Maîtres en Maniement de Rondache Tryker";s:11:"description";s:39:"Maître en Maniement de Rondache Tryker";}s:5:"sdsbz";a:3:{s:4:"name";s:22:"Manier Rondache Zoraï";s:1:"p";s:22:"Manier Rondache Zoraï";s:11:"description";s:22:"Manier Rondache Zoraï";}s:6:"sdsbza";a:3:{s:4:"name";s:38:"Expert en Maniement de Rondache Zoraï";s:1:"p";s:39:"Experts en Maniement de Rondache Zoraï";s:11:"description";s:38:"Expert en Maniement de Rondache Zoraï";}s:7:"sdsbzae";a:3:{s:4:"name";s:39:"Maître en Maniement de Rondache Zoraï";s:1:"p";s:40:"Maîtres en Maniement de Rondache Zoraï";s:11:"description";s:39:"Maître en Maniement de Rondache Zoraï";}s:4:"sdss";a:3:{s:4:"name";s:21:"Manier Grand Bouclier";s:1:"p";s:22:"Manier Grands Bouclier";s:11:"description";s:22:"Manier Grands Bouclier";}s:5:"sdssf";a:3:{s:4:"name";s:27:"Manier Grand Bouclier Fyros";s:1:"p";s:28:"Manier Grands Bouclier Fyros";s:11:"description";s:28:"Manier Grands Bouclier Fyros";}s:6:"sdssfa";a:3:{s:4:"name";s:43:"Expert en Maniement de Grand Bouclier Fyros";s:1:"p";s:45:"Experts en Maniement de Grands Bouclier Fyros";s:11:"description";s:44:"Expert en Maniement de Grands Bouclier Fyros";}s:7:"sdssfae";a:3:{s:4:"name";s:44:"Maître en Maniement de Grand Bouclier Fyros";s:1:"p";s:46:"Maîtres en Maniement de Grands Bouclier Fyros";s:11:"description";s:45:"Maître en Maniement de Grands Bouclier Fyros";}s:5:"sdssm";a:3:{s:4:"name";s:27:"Manier Grand Bouclier Matis";s:1:"p";s:28:"Manier Grands Bouclier Matis";s:11:"description";s:28:"Manier Grands Bouclier Matis";}s:6:"sdssma";a:3:{s:4:"name";s:43:"Expert en Maniement de Grand Bouclier Matis";s:1:"p";s:45:"Experts en Maniement de Grands Bouclier Matis";s:11:"description";s:44:"Expert en Maniement de Grands Bouclier Matis";}s:7:"sdssmae";a:3:{s:4:"name";s:44:"Maître en Maniement de Grand Bouclier Matis";s:1:"p";s:46:"Maîtres en Maniement de Grands Bouclier Matis";s:11:"description";s:45:"Maître en Maniement de Grands Bouclier Matis";}s:5:"sdsst";a:3:{s:4:"name";s:28:"Manier Grand Bouclier Tryker";s:1:"p";s:29:"Manier Grands Bouclier Tryker";s:11:"description";s:29:"Manier Grands Bouclier Tryker";}s:6:"sdssta";a:3:{s:4:"name";s:44:"Expert en Maniement de Grand Bouclier Tryker";s:1:"p";s:46:"Experts en Maniement de Grands Bouclier Tryker";s:11:"description";s:45:"Expert en Maniement de Grands Bouclier Tryker";}s:7:"sdsstae";a:3:{s:4:"name";s:45:"Maître en Maniement de Grand Bouclier Tryker";s:1:"p";s:47:"Maîtres en Maniement de Grands Bouclier Tryker";s:11:"description";s:46:"Maître en Maniement de Grands Bouclier Tryker";}s:5:"sdssz";a:3:{s:4:"name";s:28:"Manier Grand Bouclier Zoraï";s:1:"p";s:29:"Manier Grands Bouclier Zoraï";s:11:"description";s:29:"Manier Grands Bouclier Zoraï";}s:6:"sdssza";a:3:{s:4:"name";s:44:"Expert en Maniement de Grand Bouclier Zoraï";s:1:"p";s:46:"Experts en Maniement de Grands Bouclier Zoraï";s:11:"description";s:45:"Expert en Maniement de Grands Bouclier Zoraï";}s:7:"sdsszae";a:3:{s:4:"name";s:45:"Maître en Maniement de Grand Bouclier Zoraï";s:1:"p";s:47:"Maîtres en Maniement de Grands Bouclier Zoraï";s:11:"description";s:46:"Maître en Maniement de Grands Bouclier Zoraï";}s:3:"sdd";a:3:{s:4:"name";s:7:"Esquive";s:1:"p";s:8:"Esquives";s:11:"description";s:7:"Esquive";}s:4:"sdda";a:3:{s:4:"name";s:14:"Esquive Rapide";s:1:"p";s:16:"Esquives Rapides";s:11:"description";s:14:"Esquive Rapide";}s:5:"sddat";a:3:{s:4:"name";s:19:"Esquive Instinctive";s:1:"p";s:21:"Esquives Instinctives";s:11:"description";s:19:"Esquive Instinctive";}s:6:"sddatm";a:3:{s:4:"name";s:17:"Expert en Esquive";s:1:"p";s:18:"Experts en Esquive";s:11:"description";s:17:"Expert en Esquive";}s:7:"sddatme";a:3:{s:4:"name";s:18:"Maître en Esquive";s:1:"p";s:19:"Maîtres en Esquive";s:11:"description";s:18:"Maître en Esquive";}s:6:"uknown";a:3:{s:4:"name";s:20:"Compétence Inconnue";s:1:"p";s:22:"Compétences Inconnues";s:11:"description";s:20:"Compétence Inconnue";}s:7:"unknown";a:3:{s:4:"name";s:20:"Compétence Inconnue";s:1:"p";s:22:"Compétences Inconnues";s:11:"description";s:20:"Compétence Inconnue";}s:2:"sh";a:3:{s:4:"name";s:33:"Extraire les Matières Premières";s:1:"p";s:33:"Extraire les Matières Premières";s:11:"description";s:33:"Extraire les Matières Premières";}s:3:"shf";a:3:{s:4:"name";s:11:"Prospection";s:1:"p";s:12:"Prospections";s:11:"description";s:0:"";}s:4:"shff";a:3:{s:4:"name";s:23:"Prospection Forestière";s:1:"p";s:25:"Prospections Forestières";s:11:"description";s:0:"";}s:5:"shffa";a:3:{s:4:"name";s:34:"Initié en Prospection Forestière";s:1:"p";s:35:"Initiés en Prospection Forestière";s:11:"description";s:0:"";}s:6:"shffae";a:3:{s:4:"name";s:33:"Expert en Prospection Forestière";s:1:"p";s:34:"Experts en Prospection Forestière";s:11:"description";s:0:"";}s:7:"shffaem";a:3:{s:4:"name";s:34:"Maître en Prospection Forestière";s:1:"p";s:35:"Maîtres en Prospection Forestière";s:11:"description";s:0:"";}s:4:"shfd";a:3:{s:4:"name";s:23:"Prospection Désertique";s:1:"p";s:25:"Prospections Désertiques";s:11:"description";s:0:"";}s:5:"shfda";a:3:{s:4:"name";s:34:"Initié en Prospection Désertique";s:1:"p";s:35:"Initiés en Prospection Désertique";s:11:"description";s:0:"";}s:6:"shfdae";a:3:{s:4:"name";s:33:"Expert en Prospection Désertique";s:1:"p";s:34:"Experts en Prospection Désertique";s:11:"description";s:0:"";}s:7:"shfdaem";a:3:{s:4:"name";s:34:"Maître en Prospection Désertique";s:1:"p";s:35:"Maîtres en Prospection Désertique";s:11:"description";s:0:"";}s:4:"shfj";a:3:{s:4:"name";s:18:"Prospection Jungle";s:1:"p";s:12:"Prospections";s:11:"description";s:0:"";}s:5:"shfja";a:3:{s:4:"name";s:35:"Initié en Prospection de la Jungle";s:1:"p";s:36:"Initiés en Prospection de la Jungle";s:11:"description";s:0:"";}s:6:"shfjae";a:3:{s:4:"name";s:34:"Expert en Prospection de la Jungle";s:1:"p";s:35:"Experts en Prospection de la Jungle";s:11:"description";s:0:"";}s:7:"shfjaem";a:3:{s:4:"name";s:36:"Maître en Prospection de la Jungle ";s:1:"p";s:37:"Maîtres en Prospection de la Jungle ";s:11:"description";s:0:"";}s:4:"shfl";a:3:{s:4:"name";s:20:"Prospection Lacustre";s:1:"p";s:22:"Prospections Lacustres";s:11:"description";s:0:"";}s:5:"shfla";a:3:{s:4:"name";s:31:"Initié en Prospection Lacustre";s:1:"p";s:32:"Initiés en Prospection Lacustre";s:11:"description";s:0:"";}s:6:"shflae";a:3:{s:4:"name";s:30:"Expert en Prospection Lacustre";s:1:"p";s:31:"Experts en Prospection Lacustre";s:11:"description";s:0:"";}s:7:"shflaem";a:3:{s:4:"name";s:31:"Maître en Prospection Lacustre";s:1:"p";s:32:"Maîtres en Prospection Lacustre";s:11:"description";s:0:"";}s:4:"shfp";a:3:{s:4:"name";s:30:"Prospection des Primes Racines";s:1:"p";s:31:"Prospections des Primes Racines";s:11:"description";s:0:"";}s:5:"shfpa";a:3:{s:4:"name";s:41:"Initié en Prospection des Primes Racines";s:1:"p";s:42:"Initiés en Prospection des Primes Racines";s:11:"description";s:0:"";}s:6:"shfpae";a:3:{s:4:"name";s:40:"Expert en Prospection des Primes Racines";s:1:"p";s:41:"Experts en Prospection des Primes Racines";s:11:"description";s:0:"";}s:7:"shfpaem";a:3:{s:4:"name";s:41:"Maître en Prospection des Primes Racines";s:1:"p";s:42:"Maîtres en Prospection des Primes Racines";s:11:"description";s:0:"";}s:7:"smtoeca";a:3:{s:4:"name";s:25:"Initié en Magie du Froid";s:1:"p";s:26:"Initiés en Magie du Froid";s:11:"description";s:67:"Magie offensive avancée ciblée sur les dégâts spéciaux - Froid";}s:7:"smtoeaa";a:3:{s:4:"name";s:24:"Initié en Magie d\'Acide";s:1:"p";s:25:"Initiés en Magie d\'Acide";s:11:"description";s:67:"Magie offensive avancée ciblée sur les dégâts spéciaux - Acide";}s:7:"smtoera";a:3:{s:4:"name";s:33:"Initié en Magie de la Pourriture";s:1:"p";s:34:"Initiés en Magie de la Pourriture";s:11:"description";s:72:"Magie offensive avancée ciblée sur les dégâts spéciaux - Pourriture";}s:7:"smtoefa";a:3:{s:4:"name";s:23:"Initié en Magie du Feu";s:1:"p";s:24:"Initiés en Magie du Feu";s:11:"description";s:65:"Magie offensive avancée ciblée sur les dégâts spéciaux - Feu";}s:7:"smtoesa";a:3:{s:4:"name";s:34:"Initié en Magie des Ondes de Choc";s:1:"p";s:35:"Initiés en Magie des Ondes de Choc";s:11:"description";s:74:"Magie offensive avancée ciblée sur les dégâts spéciaux - Onde de Choc";}s:7:"smtoepa";a:3:{s:4:"name";s:26:"Initié en Magie du Poison";s:1:"p";s:27:"Initiés en Magie du Poison";s:11:"description";s:68:"Magie offensive avancée ciblée sur les dégâts spéciaux - Poison";}s:7:"smtoeea";a:3:{s:4:"name";s:27:"Initié en Magie Electrique";s:1:"p";s:28:"Initiés en Magie Electrique";s:11:"description";s:74:"Magie offensive avancée ciblée sur les dégâts spéciaux - Electricité";}s:7:"smtchpa";a:3:{s:4:"name";s:33:"Initié en Magie de Guérison Vie";s:1:"p";s:34:"Initiés en Magie de Guérison Vie";s:11:"description";s:54:"Magie Curative Avancée Ciblée sur la Guérison - Vie";}s:7:"smtchta";a:3:{s:4:"name";s:33:"Initié en Magie de Guérison Sta";s:1:"p";s:34:"Initiés en Magie de Guérison Sta";s:11:"description";s:54:"Magie Curative Avancée Ciblée sur la Guérison - Sta";}s:7:"smtchaa";a:3:{s:4:"name";s:35:"Initié en Magie de Guérison Sève";s:1:"p";s:36:"Initiés en Magie de Guérison Sève";s:11:"description";s:56:"Magie Curative Avancée Ciblée sur la Guérison - Sève";}s:7:"smtchca";a:3:{s:4:"name";s:43:"Initié en Magie de Guérison Concentration";s:1:"p";s:44:"Initiés en Magie de Guérison Concentration";s:11:"description";s:64:"Magie curative avancée ciblée sur la guérison - Concentration";}s:7:"smlosma";a:3:{s:4:"name";s:24:"Initié en Lien de Folie";s:1:"p";s:25:"Initiés en Lien de Folie";s:11:"description";s:60:"Lien avancé de magie offensive relatif aux maladies - Folie";}s:7:"smlossa";a:3:{s:4:"name";s:43:"Initié en Lien de Diminution de la Vitesse";s:1:"p";s:44:"Initiés en Lien de Diminution de la Vitesse";s:11:"description";s:79:"Lien avancé de magie offensive relatif aux maladies - Diminution de la Vitesse";}s:7:"smlocia";a:3:{s:4:"name";s:29:"Initié en Lien d\'Incapacité";s:1:"p";s:30:"Initiés en Lien d\'Incapacité";s:11:"description";s:71:"Lien avancé de magie offensive relatif aux malédictions - Incapacité";}s:7:"smlocsa";a:3:{s:4:"name";s:28:"Initié en Lien de Malchance";s:1:"p";s:29:"Initiés en Lien de Malchance";s:11:"description";s:69:"Lien avancé de magie offensive relatif aux malédictions - Malchance";}s:7:"smloeca";a:3:{s:4:"name";s:24:"Initié en Lien du Froid";s:1:"p";s:25:"Initiés en Lien du Froid";s:11:"description";s:70:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Froid";}s:7:"smloeaa";a:3:{s:4:"name";s:23:"Initié en Lien d\'Acide";s:1:"p";s:24:"Initiés en Lien d\'Acide";s:11:"description";s:70:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Acide";}s:7:"smloera";a:3:{s:4:"name";s:29:"Initié en Lien de Pourriture";s:1:"p";s:30:"Initiés en Lien de Pourriture";s:11:"description";s:75:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Pourriture";}s:7:"smloefa";a:3:{s:4:"name";s:22:"Initié en Lien de Feu";s:1:"p";s:23:"Initiés en Lien de Feu";s:11:"description";s:68:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Feu";}s:7:"smloesa";a:3:{s:4:"name";s:31:"Initié en Lien d\'Ondes de Choc";s:1:"p";s:32:"Initiés en Lien d\'Ondes de Choc";s:11:"description";s:78:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Ondes de Choc";}s:7:"smloepa";a:3:{s:4:"name";s:25:"Initié en Lien de Poison";s:1:"p";s:26:"Initiés en Lien de Poison";s:11:"description";s:71:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Poison";}s:7:"smloeea";a:3:{s:4:"name";s:27:"Initié en Lien électrique";s:1:"p";s:28:"Initiés en Lien électrique";s:11:"description";s:77:"Lien avancé de magie offensive relatif aux dégâts spéciaux - Electricité";}s:3:"smd";a:3:{s:4:"name";s:16:"Magie Salvatrice";s:1:"p";s:18:"Magies Salvatrices";s:11:"description";s:16:"Magie Salvatrice";}s:4:"smda";a:3:{s:4:"name";s:19:"Magie Neutralisante";s:1:"p";s:21:"Magies Neutralisantes";s:11:"description";s:19:"Magie Neutralisante";}s:5:"smdaa";a:3:{s:4:"name";s:30:"Initié en Magie Neutralisante";s:1:"p";s:31:"Initiés en Magie Neutralisante";s:11:"description";s:30:"Initié en Magie Neutralisante";}s:6:"smdaae";a:3:{s:4:"name";s:29:"Expert en Magie Neutralisante";s:1:"p";s:30:"Experts en Magie Neutralisante";s:11:"description";s:29:"Expert en Magie Neutralisante";}s:7:"smdaaem";a:3:{s:4:"name";s:30:"Maître en Magie Neutralisante";s:1:"p";s:31:"Maîtres en Magie Neutralisante";s:11:"description";s:30:"Maître en Magie Neutralisante";}s:4:"smdh";a:3:{s:4:"name";s:14:"Magie Curative";s:1:"p";s:16:"Magies Curatives";s:11:"description";s:14:"Magie Curative";}s:5:"smdha";a:3:{s:4:"name";s:25:"Initié en Magie Curative";s:1:"p";s:26:"Initiés en Magie Curative";s:11:"description";s:25:"Initié en Magie Curative";}s:6:"smdhae";a:3:{s:4:"name";s:24:"Expert en Magie Curative";s:1:"p";s:25:"Experts en Magie Curative";s:11:"description";s:24:"Expert en Magie Curative";}s:7:"smdhaem";a:3:{s:4:"name";s:25:"Maître en Magie Curative";s:1:"p";s:26:"Maîtres en Magie Curative";s:11:"description";s:25:"Maître en Magie Curative";}s:3:"smo";a:3:{s:4:"name";s:18:"Magie Destructrice";s:1:"p";s:20:"Magies Destructrices";s:11:"description";s:18:"Magie Destructrice";}s:4:"smoa";a:3:{s:4:"name";s:18:"Magie Débilitante";s:1:"p";s:20:"Magies Débilitantes";s:11:"description";s:18:"Magie Débilitante";}s:5:"smoaa";a:3:{s:4:"name";s:29:"Initié en Magie Débilitante";s:1:"p";s:30:"Initiés en Magie Débilitante";s:11:"description";s:29:"Initié en Magie Débilitante";}s:6:"smoaae";a:3:{s:4:"name";s:28:"Expert en Magie Débilitante";s:1:"p";s:29:"Experts en Magie Débilitante";s:11:"description";s:28:"Expert en Magie Débilitante";}s:7:"smoaaem";a:3:{s:4:"name";s:29:"Maître en Magie Débilitante";s:1:"p";s:30:"Maîtres en Magie Débilitante";s:11:"description";s:29:"Maître en Magie Débilitante";}s:4:"smoe";a:3:{s:4:"name";s:18:"Magie Elémentaire";s:1:"p";s:20:"Magies Elémentaires";s:11:"description";s:18:"Magie Elémentaire";}s:5:"smoea";a:3:{s:4:"name";s:29:"Initié en Magie Elémentaire";s:1:"p";s:30:"Initiés en Magie Elémentaire";s:11:"description";s:29:"Initié en Magie Elémentaire";}s:6:"smoeae";a:3:{s:4:"name";s:28:"Expert en Magie Elémentaire";s:1:"p";s:29:"Experts en Magie Elémentaire";s:11:"description";s:28:"Expert en Magie Elémentaire";}s:7:"smoeaem";a:3:{s:4:"name";s:29:"Maître en Magie Elémentaire";s:1:"p";s:30:"Maîtres en Magie Elémentaire";s:11:"description";s:29:"Maître en Magie Elémentaire";}s:3:"sms";a:3:{s:4:"name";s:13:"Magie sur Soi";s:1:"p";s:14:"Magies sur Soi";s:11:"description";s:13:"Magie sur Soi";}s:4:"smss";a:3:{s:4:"name";s:25:"Magie sur Soi Instinctive";s:1:"p";s:26:"Magies sur Soi Instinctive";s:11:"description";s:25:"Magie sur Soi Instinctive";}s:5:"smssa";a:3:{s:4:"name";s:24:"Initié en Magie sur Soi";s:1:"p";s:25:"Initiés en Magie sur Soi";s:11:"description";s:24:"Initié en Magie sur Soi";}s:6:"smssae";a:3:{s:4:"name";s:23:"Expert en Magie sur Soi";s:1:"p";s:24:"Experts en Magie sur Soi";s:11:"description";s:23:"Expert en Magie sur Soi";}s:7:"smssaem";a:3:{s:4:"name";s:24:"Maître en Magie sur Soi";s:1:"p";s:25:"Maîtres en Magie sur Soi";s:11:"description";s:24:"Maître en Magie sur Soi";}s:2:"sf";a:3:{s:4:"name";s:6:"Combat";s:1:"p";s:7:"Combats";s:11:"description";s:0:"";}s:3:"sfm";a:3:{s:4:"name";s:7:"Mêlée";s:1:"p";s:8:"Mêlées";s:11:"description";s:0:"";}s:4:"sfm1";a:3:{s:4:"name";s:24:"Manier Armes à une Main";s:1:"p";s:24:"Manier Armes à une Main";s:11:"description";s:0:"";}s:5:"sfm1b";a:3:{s:4:"name";s:37:"Manier Armes à une Main Contondantes";s:1:"p";s:37:"Manier Armes à une Main Contondantes";s:11:"description";s:0:"";}s:6:"sfm1bm";a:3:{s:4:"name";s:13:"Manier Massue";s:1:"p";s:13:"Manier Massue";s:11:"description";s:0:"";}s:7:"sfm1bme";a:3:{s:4:"name";s:24:"Manier Massue Electrique";s:1:"p";s:26:"Manier Massues Electriques";s:11:"description";s:0:"";}s:7:"sfm1bmm";a:3:{s:4:"name";s:32:"Maître en Maniement des Massues";s:1:"p";s:33:"Maîtres en Maniement des Massues";s:11:"description";s:0:"";}s:6:"sfm1bs";a:3:{s:4:"name";s:13:"Manier Bâton";s:1:"p";s:14:"Manier Bâtons";s:11:"description";s:0:"";}s:7:"sfm1bsm";a:3:{s:4:"name";s:32:"Maître en Maniement des Bâtons";s:1:"p";s:33:"Maîtres en Maniement des Bâtons";s:11:"description";s:0:"";}s:5:"sfm1p";a:3:{s:4:"name";s:36:"Manier Armes à une Main Perforantes";s:1:"p";s:36:"Manier Armes à une Main Perforantes";s:11:"description";s:0:"";}s:6:"sfm1ps";a:3:{s:4:"name";s:12:"Manier Lance";s:1:"p";s:13:"Manier Lances";s:11:"description";s:0:"";}s:7:"sfm1pse";a:3:{s:4:"name";s:23:"Manier Lance Electrique";s:1:"p";s:25:"Manier Lances Electriques";s:11:"description";s:0:"";}s:7:"sfm1psl";a:3:{s:4:"name";s:20:"Manier Lance Vivante";s:1:"p";s:22:"Manier Lances Vivantes";s:11:"description";s:0:"";}s:7:"sfm1psm";a:3:{s:4:"name";s:31:"Maître en Maniement des Lances";s:1:"p";s:32:"Maîtres en Maniement des Lances";s:11:"description";s:0:"";}s:5:"sfm1s";a:3:{s:4:"name";s:36:"Manier Armes à une Main Tranchantes";s:1:"p";s:36:"Manier Armes à une Main Tranchantes";s:11:"description";s:0:"";}s:6:"sfm1sa";a:3:{s:4:"name";s:12:"Manier Hache";s:1:"p";s:13:"Manier Haches";s:11:"description";s:0:"";}s:7:"sfm1sab";a:3:{s:4:"name";s:20:"Manier Hache Ardente";s:1:"p";s:22:"Manier Haches Ardentes";s:11:"description";s:0:"";}s:7:"sfm1sam";a:3:{s:4:"name";s:31:"Maître en Maniement des Haches";s:1:"p";s:32:"Maîtres en Maniement des Haches";s:11:"description";s:0:"";}s:6:"sfm1ss";a:3:{s:4:"name";s:12:"Manier Epée";s:1:"p";s:13:"Manier Epées";s:11:"description";s:0:"";}s:7:"sfm1ssm";a:3:{s:4:"name";s:31:"Maître en Maniement des Epées";s:1:"p";s:32:"Maîtres en Maniement des Epées";s:11:"description";s:0:"";}s:7:"sfm1ssw";a:3:{s:4:"name";s:22:"Manier Epée Ondulante";s:1:"p";s:24:"Manier Epées Ondulantes";s:11:"description";s:0:"";}s:4:"sfm2";a:3:{s:4:"name";s:26:"Manier Armes à deux Mains";s:1:"p";s:26:"Manier Armes à deux Mains";s:11:"description";s:0:"";}s:5:"sfm2b";a:3:{s:4:"name";s:39:"Manier Armes à deux Mains Contondantes";s:1:"p";s:39:"Manier Armes à deux Mains Contondantes";s:11:"description";s:0:"";}s:6:"sfm2bm";a:3:{s:4:"name";s:27:"Manier Massue à deux Mains";s:1:"p";s:28:"Manier Massues à deux Mains";s:11:"description";s:0:"";}s:7:"sfm2bme";a:3:{s:4:"name";s:39:"Manier Massue à deux Mains Electrique ";s:1:"p";s:40:"Manier Massues à deux Mains Electriques";s:11:"description";s:0:"";}s:7:"sfm2bmm";a:3:{s:4:"name";s:46:"Maître en Maniement des Massues à deux Mains";s:1:"p";s:47:"Maîtres en Maniement des Massues à deux Mains";s:11:"description";s:0:"";}s:5:"sfm2p";a:3:{s:4:"name";s:38:"Manier Armes à deux Mains Perforantes";s:1:"p";s:38:"Manier Armes à deux Mains Perforantes";s:11:"description";s:0:"";}s:6:"sfm2pp";a:3:{s:4:"name";s:12:"Manier Pique";s:1:"p";s:13:"Manier Piques";s:11:"description";s:0:"";}s:7:"sfm2ppl";a:3:{s:4:"name";s:20:"Manier Pique Vivante";s:1:"p";s:22:"Manier Piques Vivantes";s:11:"description";s:0:"";}s:7:"sfm2ppm";a:3:{s:4:"name";s:32:"Maître en Maniement de la Pique";s:1:"p";s:33:"Maîtres en Maniement de la Pique";s:11:"description";s:0:"";}s:5:"sfm2s";a:3:{s:4:"name";s:38:"Manier Armes à deux Mains Tranchantes";s:1:"p";s:38:"Manier Armes à deux Mains Tranchantes";s:11:"description";s:0:"";}s:6:"sfm2sa";a:3:{s:4:"name";s:26:"Manier Hache à deux Mains";s:1:"p";s:27:"Manier Haches à deux Mains";s:11:"description";s:0:"";}s:7:"sfm2sab";a:3:{s:4:"name";s:34:"Manier Hache Ardente à deux Mains";s:1:"p";s:36:"Manier Haches Ardentes à deux Mains";s:11:"description";s:0:"";}s:7:"sfm2sam";a:3:{s:4:"name";s:45:"Maître en Maniement des Haches à deux Mains";s:1:"p";s:45:"Maître en Maniement des Haches à deux Mains";s:11:"description";s:0:"";}s:6:"sfm2ss";a:3:{s:4:"name";s:26:"Manier Epée à deux Mains";s:1:"p";s:27:"Manier Epées à deux Mains";s:11:"description";s:0:"";}s:7:"sfm2ssb";a:3:{s:4:"name";s:34:"Manier Epée à deux Mains Ardente";s:1:"p";s:36:"Manier Epées à deux Mains Ardentes";s:11:"description";s:0:"";}s:7:"sfm2ssl";a:3:{s:4:"name";s:34:"Manier Epée à deux Mains Vivante";s:1:"p";s:36:"Manier Epées à deux Mains Vivantes";s:11:"description";s:0:"";}s:7:"sfm2ssm";a:3:{s:4:"name";s:45:"Maître en Maniement des Epées à deux Mains";s:1:"p";s:46:"Maîtres en Maniement des Epées à deux Mains";s:11:"description";s:0:"";}s:7:"sfm2ssw";a:3:{s:4:"name";s:36:"Manier Epée à deux Mains Ondulante";s:1:"p";s:38:"Manier Epées à deux Mains Ondulantes";s:11:"description";s:0:"";}s:4:"sfmc";a:3:{s:4:"name";s:17:"Combat Rapproché";s:1:"p";s:19:"Combats Rapprochés";s:11:"description";s:0:"";}s:5:"sfmca";a:3:{s:4:"name";s:28:"Initié en Combat Rapproché";s:1:"p";s:29:"Initiés en Combat Rapproché";s:11:"description";s:0:"";}s:6:"sfmcad";a:3:{s:4:"name";s:12:"Manier Dague";s:1:"p";s:13:"Manier Dagues";s:11:"description";s:0:"";}s:7:"sfmcadl";a:3:{s:4:"name";s:20:"Manier Dague Vivante";s:1:"p";s:22:"Manier Dagues Vivantes";s:11:"description";s:0:"";}s:7:"sfmcadm";a:3:{s:4:"name";s:32:"Maître en Maniement de la Dague";s:1:"p";s:33:"Maîtres en Maniement de la Dague";s:11:"description";s:0:"";}s:7:"sfmcadw";a:3:{s:4:"name";s:22:"Manier Dague Ondulante";s:1:"p";s:24:"Manier Dagues Ondulantes";s:11:"description";s:0:"";}s:6:"sfmcah";a:3:{s:4:"name";s:30:"Expert en Combat à Mains Nues";s:1:"p";s:31:"Experts en Combat à Mains Nues";s:11:"description";s:0:"";}s:7:"sfmcahm";a:3:{s:4:"name";s:31:"Maître en Combat à Mains Nues";s:1:"p";s:32:"Maîtres en Combat à Mains Nues";s:11:"description";s:0:"";}s:3:"sfr";a:3:{s:4:"name";s:3:"Tir";s:1:"p";s:4:"Tirs";s:11:"description";s:0:"";}s:4:"sfr1";a:3:{s:4:"name";s:15:"Tir à une Main";s:1:"p";s:16:"Tirs à une Main";s:11:"description";s:0:"";}s:5:"sfr1a";a:3:{s:4:"name";s:26:"Initié au Tir à une Main";s:1:"p";s:27:"Initiés au Tir à une Main";s:11:"description";s:0:"";}s:6:"sfr1ab";a:3:{s:4:"name";s:25:"Tir au Pistolet-Arbalète";s:1:"p";s:26:"Tir sau Pistolet-Arbalète";s:11:"description";s:0:"";}s:7:"sfr1abm";a:3:{s:4:"name";s:36:"Maître en Tir au Pistolet-Arbalète";s:1:"p";s:37:"Maîtres en Tir au Pistolet-Arbalète";s:11:"description";s:0:"";}s:7:"sfr1abw";a:3:{s:4:"name";s:34:"Tir au Pistolet-Arbalète Ondulant";s:1:"p";s:35:"Tirs au Pistolet-Arbalète Ondulant";s:11:"description";s:0:"";}s:6:"sfr1ap";a:3:{s:4:"name";s:15:"Tir au Pistolet";s:1:"p";s:16:"Tirs au Pistolet";s:11:"description";s:0:"";}s:7:"sfr1apl";a:3:{s:4:"name";s:22:"Tir au Pistolet Vivant";s:1:"p";s:23:"Tirs au Pistolet Vivant";s:11:"description";s:0:"";}s:7:"sfr1apm";a:3:{s:4:"name";s:27:"Maître en Tir au Pistolet ";s:1:"p";s:28:"Maîtres en Tir au Pistolet ";s:11:"description";s:0:"";}s:4:"sfr2";a:3:{s:4:"name";s:17:"Tir à deux Mains";s:1:"p";s:18:"Tirs à deux Mains";s:11:"description";s:0:"";}s:5:"sfr2a";a:3:{s:4:"name";s:28:"Initié au Tir à deux Mains";s:1:"p";s:29:"Initiés au Tir à deux Mains";s:11:"description";s:0:"";}s:6:"sfr2aa";a:3:{s:4:"name";s:22:"Tir à la Mitrailleuse";s:1:"p";s:23:"Tirs à la Mitrailleuse";s:11:"description";s:0:"";}s:7:"sfr2aab";a:3:{s:4:"name";s:30:"Tir à la Mitrailleuse Ardente";s:1:"p";s:31:"Tirs à la Mitrailleuse Ardente";s:11:"description";s:0:"";}s:7:"sfr2aam";a:3:{s:4:"name";s:33:"Maître en Tir à la Mitrailleuse";s:1:"p";s:34:"Maîtres en Tir à la Mitrailleuse";s:11:"description";s:0:"";}s:6:"sfr2ab";a:3:{s:4:"name";s:22:"Tir au Fusil-Arbalète";s:1:"p";s:23:"Tirs au Fusil-Arbalète";s:11:"description";s:0:"";}s:7:"sfr2abe";a:3:{s:4:"name";s:33:"Tir au Fusil-Arbalète Electrique";s:1:"p";s:34:"Tirs au Fusil-Arbalète Electrique";s:11:"description";s:0:"";}s:7:"sfr2abm";a:3:{s:4:"name";s:33:"Maître en Tir au Fusil-Arbalète";s:1:"p";s:34:"Maîtres en Tir au Fusil-Arbalète";s:11:"description";s:0:"";}s:6:"sfr2al";a:3:{s:4:"name";s:21:"Tir au Lance-Grenades";s:1:"p";s:22:"Tirs au Lance-Grenades";s:11:"description";s:0:"";}s:7:"sfr2alb";a:3:{s:4:"name";s:28:"Tir au Lance-Grenades Ardent";s:1:"p";s:29:"Tirs au Lance-Grenades Ardent";s:11:"description";s:0:"";}s:7:"sfr2ale";a:3:{s:4:"name";s:32:"Tir au Lance-Grenades Electrique";s:1:"p";s:33:"Tirs au Lance-Grenades Electrique";s:11:"description";s:0:"";}s:7:"sfr2alm";a:3:{s:4:"name";s:32:"Maître en Tir au Lance-Grenades";s:1:"p";s:33:"Maîtres en Tir au Lance-Grenades";s:11:"description";s:0:"";}s:6:"sfr2ar";a:3:{s:4:"name";s:12:"Tir au Fusil";s:1:"p";s:13:"Tirs au Fusil";s:11:"description";s:0:"";}s:7:"sfr2arl";a:3:{s:4:"name";s:19:"Tir au Fusil Vivant";s:1:"p";s:20:"Tirs au Fusil Vivant";s:11:"description";s:0:"";}s:7:"sfr2arm";a:3:{s:4:"name";s:24:"Maître en Tir au Fusil ";s:1:"p";s:25:"Maîtres en Tir au Fusil ";s:11:"description";s:0:"";}s:3:"sca";a:3:{s:4:"name";s:13:"Créer Armure";s:1:"p";s:13:"Créer Armure";s:11:"description";s:13:"Créer Armure";}s:3:"scj";a:3:{s:4:"name";s:12:"Créer Bijou";s:1:"p";s:12:"Créer Bijou";s:11:"description";s:12:"Créer Bijou";}s:3:"scm";a:3:{s:4:"name";s:22:"Créer Arme de Mêlée";s:1:"p";s:22:"Créer Arme de Mêlée";s:11:"description";s:22:"Créer Arme de Mêlée";}s:3:"scr";a:3:{s:4:"name";s:18:"Créer Arme de Tir";s:1:"p";s:18:"Créer Arme de Tir";s:11:"description";s:18:"Créer Arme de Tir";}s:4:"scah";a:3:{s:4:"name";s:20:"Créer Armure Lourde";s:1:"p";s:20:"Créer Armure Lourde";s:11:"description";s:20:"Créer Armure Lourde";}s:4:"scal";a:3:{s:4:"name";s:22:"Créer Armure Légère";s:1:"p";s:22:"Créer Armure Légère";s:11:"description";s:22:"Créer Armure Légère";}s:4:"scam";a:3:{s:4:"name";s:21:"Créer Armure Moyenne";s:1:"p";s:21:"Créer Armure Moyenne";s:11:"description";s:21:"Créer Armure Moyenne";}s:4:"scas";a:3:{s:4:"name";s:15:"Créer Bouclier";s:1:"p";s:15:"Créer Bouclier";s:11:"description";s:15:"Créer Bouclier";}s:4:"scja";a:3:{s:4:"name";s:25:"Créer Anneau de Cheville";s:1:"p";s:25:"Créer Anneau de Cheville";s:11:"description";s:25:"Créer Anneau de Cheville";}s:4:"scjb";a:3:{s:4:"name";s:15:"Créer Bracelet";s:1:"p";s:15:"Créer Bracelet";s:11:"description";s:15:"Créer Bracelet";}s:4:"scjd";a:3:{s:4:"name";s:15:"Créer Diadème";s:1:"p";s:15:"Créer Diadème";s:11:"description";s:15:"Créer Diadème";}s:4:"scje";a:3:{s:4:"name";s:23:"Créer Boucle d\'Oreille";s:1:"p";s:23:"Créer Boucle d\'Oreille";s:11:"description";s:23:"Créer Boucle d\'Oreille";}s:4:"scjp";a:3:{s:4:"name";s:16:"Créer Pendentif";s:1:"p";s:16:"Créer Pendentif";s:11:"description";s:16:"Créer Pendentif";}s:4:"scjr";a:3:{s:4:"name";s:12:"Créer Bague";s:1:"p";s:12:"Créer Bague";s:11:"description";s:12:"Créer Bague";}s:4:"scm1";a:3:{s:4:"name";s:34:"Créer Arme de Mêlée à une Main";s:1:"p";s:34:"Créer Arme de Mêlée à une Main";s:11:"description";s:34:"Créer Arme de Mêlée à une Main";}s:4:"scm2";a:3:{s:4:"name";s:36:"Créer Arme de Mêlée à deux Mains";s:1:"p";s:36:"Créer Arme de Mêlée à deux Mains";s:11:"description";s:36:"Créer Arme de Mêlée à deux Mains";}s:4:"scr1";a:3:{s:4:"name";s:30:"Créer Arme de Tir à une Main";s:1:"p";s:30:"Créer Arme de Tir à une Main";s:11:"description";s:30:"Créer Arme de Tir à une Main";}s:4:"scr2";a:3:{s:4:"name";s:32:"Créer Arme de Tir à deux Mains";s:1:"p";s:32:"Créer Arme de Tir à deux Mains";s:11:"description";s:32:"Créer Arme de Tir à deux Mains";}s:5:"scahb";a:3:{s:4:"name";s:21:"Créer Bottes Lourdes";s:1:"p";s:21:"Créer Bottes Lourdes";s:11:"description";s:21:"Créer Bottes Lourdes";}s:5:"scahg";a:3:{s:4:"name";s:19:"Créer Gants Lourds";s:1:"p";s:19:"Créer Gants Lourds";s:11:"description";s:19:"Créer Gants Lourds";}s:5:"scahh";a:3:{s:4:"name";s:19:"Créer Casque Lourd";s:1:"p";s:19:"Créer Casque Lourd";s:11:"description";s:19:"Créer Casque Lourd";}s:5:"scahp";a:3:{s:4:"name";s:21:"Créer Pantalon Lourd";s:1:"p";s:21:"Créer Pantalon Lourd";s:11:"description";s:21:"Créer Pantalon Lourd";}s:5:"scahs";a:3:{s:4:"name";s:22:"Créer Manches Lourdes";s:1:"p";s:22:"Créer Manches Lourdes";s:11:"description";s:22:"Créer Manches Lourdes";}s:5:"scahv";a:3:{s:4:"name";s:18:"Créer Gilet Lourd";s:1:"p";s:18:"Créer Gilet Lourd";s:11:"description";s:18:"Créer Gilet Lourd";}s:5:"scalb";a:3:{s:4:"name";s:23:"Créer Bottes Légères";s:1:"p";s:23:"Créer Bottes Légères";s:11:"description";s:23:"Créer Bottes Légères";}s:5:"scalg";a:3:{s:4:"name";s:20:"Créer Gants Légers";s:1:"p";s:20:"Créer Gants Légers";s:11:"description";s:20:"Créer Gants Légers";}s:5:"scalp";a:3:{s:4:"name";s:22:"Créer Pantalon Léger";s:1:"p";s:22:"Créer Pantalon Léger";s:11:"description";s:22:"Créer Pantalon Léger";}s:5:"scals";a:3:{s:4:"name";s:24:"Créer Manches Légères";s:1:"p";s:24:"Créer Manches Légères";s:11:"description";s:24:"Créer Manches Légères";}s:5:"scalv";a:3:{s:4:"name";s:19:"Créer Gilet Léger";s:1:"p";s:19:"Créer Gilet Léger";s:11:"description";s:19:"Créer Gilet Léger";}s:5:"scamb";a:3:{s:4:"name";s:22:"Créer Bottes Moyennes";s:1:"p";s:22:"Créer Bottes Moyennes";s:11:"description";s:22:"Créer Bottes Moyennes";}s:5:"scamg";a:3:{s:4:"name";s:19:"Créer Gants Moyens";s:1:"p";s:19:"Créer Gants Moyens";s:11:"description";s:19:"Créer Gants Moyens";}s:5:"scamp";a:3:{s:4:"name";s:21:"Créer Pantalon Moyen";s:1:"p";s:21:"Créer Pantalon Moyen";s:11:"description";s:21:"Créer Pantalon Moyen";}s:5:"scams";a:3:{s:4:"name";s:23:"Créer Manches Moyennes";s:1:"p";s:23:"Créer Manches Moyennes";s:11:"description";s:23:"Créer Manches Moyennes";}s:5:"scamv";a:3:{s:4:"name";s:18:"Créer Gilet Moyen";s:1:"p";s:18:"Créer Gilet Moyen";s:11:"description";s:18:"Créer Gilet Moyen";}s:5:"scass";a:3:{s:4:"name";s:21:"Créer Grand Bouclier";s:1:"p";s:21:"Créer Grand Bouclier";s:11:"description";s:21:"Créer Grand Bouclier";}s:5:"scasb";a:3:{s:4:"name";s:15:"Créer Rondache";s:1:"p";s:15:"Créer Rondache";s:11:"description";s:15:"Créer Rondache";}s:5:"scjaa";a:3:{s:4:"name";s:41:"Initié en Création d\'Anneau de Cheville";s:1:"p";s:42:"Initiés en Création d\'Anneau de Cheville";s:11:"description";s:41:"Initié en Création d\'Anneau de Cheville";}s:5:"scjba";a:3:{s:4:"name";s:32:"Initié en Création de Bracelet";s:1:"p";s:33:"Initiés en Création de Bracelet";s:11:"description";s:32:"Initié en Création de Bracelet";}s:5:"scjda";a:3:{s:4:"name";s:32:"Initié en Création de Diadème";s:1:"p";s:33:"Initiés en Création de Diadème";s:11:"description";s:32:"Initié en Création de Diadème";}s:5:"scjea";a:3:{s:4:"name";s:40:"Initié en Création de Boucle d\'Oreille";s:1:"p";s:41:"Initiés en Création de Boucle d\'Oreille";s:11:"description";s:40:"Initié en Création de Boucle d\'Oreille";}s:5:"scjpa";a:3:{s:4:"name";s:33:"Initié en Création de Pendentif";s:1:"p";s:34:"Initiés en Création de Pendentif";s:11:"description";s:33:"Initié en Création de Pendentif";}s:5:"scjra";a:3:{s:4:"name";s:29:"Initié en Création de Bague";s:1:"p";s:30:"Initiés en Création de Bague";s:11:"description";s:29:"Initié en Création de Bague";}s:5:"scm1m";a:3:{s:4:"name";s:13:"Créer Massue";s:1:"p";s:13:"Créer Massue";s:11:"description";s:13:"Créer Massue";}s:5:"scm1t";a:3:{s:4:"name";s:13:"Créer Bâton";s:1:"p";s:13:"Créer Bâton";s:11:"description";s:13:"Créer Bâton";}s:5:"scm1p";a:3:{s:4:"name";s:12:"Créer Lance";s:1:"p";s:12:"Créer Lance";s:11:"description";s:12:"Créer Lance";}s:5:"scm1a";a:3:{s:4:"name";s:12:"Créer Hache";s:1:"p";s:12:"Créer Hache";s:11:"description";s:12:"Créer Hache";}s:5:"scm1s";a:3:{s:4:"name";s:12:"Créer Epée";s:1:"p";s:12:"Créer Epée";s:11:"description";s:12:"Créer Epée";}s:5:"scm1d";a:3:{s:4:"name";s:12:"Créer Dague";s:1:"p";s:12:"Créer Dague";s:11:"description";s:12:"Créer Dague";}s:5:"scm2m";a:3:{s:4:"name";s:27:"Créer Massue à deux Mains";s:1:"p";s:27:"Créer Massue à deux Mains";s:11:"description";s:27:"Créer Massue à deux Mains";}s:5:"scm2p";a:3:{s:4:"name";s:12:"Créer Pique";s:1:"p";s:12:"Créer Pique";s:11:"description";s:12:"Créer Pique";}s:5:"scm2a";a:3:{s:4:"name";s:26:"Créer Hache à deux Mains";s:1:"p";s:26:"Créer Hache à deux Mains";s:11:"description";s:26:"Créer Hache à deux Mains";}s:5:"scm2s";a:3:{s:4:"name";s:26:"Créer Epée à deux Mains";s:1:"p";s:27:"Créer Epéee à deux Mains";s:11:"description";s:27:"Créer Epéee à deux Mains";}s:5:"scr1b";a:3:{s:4:"name";s:25:"Créer Pistolet-Arbalète";s:1:"p";s:25:"Créer Pistolet-Arbalète";s:11:"description";s:25:"Créer Pistolet-arbalète";}s:5:"scr1p";a:3:{s:4:"name";s:16:"Créer Pistolet ";s:1:"p";s:16:"Créer Pistolet ";s:11:"description";s:16:"Créer Pistolet ";}s:5:"scr2a";a:3:{s:4:"name";s:19:"Créer Mitrailleuse";s:1:"p";s:19:"Créer Mitrailleuse";s:11:"description";s:19:"Créer Mitrailleuse";}s:5:"scr2b";a:3:{s:4:"name";s:22:"Créer Fusil-Arbalète";s:1:"p";s:22:"Créer Fusil-Arbalète";s:11:"description";s:22:"Créer Fusil-Arbalète";}s:5:"scr2l";a:3:{s:4:"name";s:21:"Créer Lance-Grenades";s:1:"p";s:21:"Créer Lance-Grenades";s:11:"description";s:21:"Créer Lance-Grenades";}s:5:"scr2r";a:3:{s:4:"name";s:13:"Créer Fusil ";s:1:"p";s:13:"Créer Fusil ";s:11:"description";s:13:"Créer Fusil ";}s:6:"scahbe";a:3:{s:4:"name";s:37:"Expert en Création de Bottes Lourdes";s:1:"p";s:38:"Experts en Création de Bottes Lourdes";s:11:"description";s:37:"Expert en Création de Bottes Lourdes";}s:6:"scahge";a:3:{s:4:"name";s:35:"Expert en Création de Gants Lourds";s:1:"p";s:36:"Experts en Création de Gants Lourds";s:11:"description";s:35:"Expert en Création de Gants Lourds";}s:6:"scahhe";a:3:{s:4:"name";s:35:"Expert en Création de Casque Lourd";s:1:"p";s:36:"Experts en Création de Casque Lourd";s:11:"description";s:35:"Expert en Création de Casque Lourd";}s:6:"scahpe";a:3:{s:4:"name";s:37:"Expert en Création de Pantalon Lourd";s:1:"p";s:38:"Experts en Création de Pantalon Lourd";s:11:"description";s:37:"Expert en Création de Pantalon Lourd";}s:6:"scahse";a:3:{s:4:"name";s:38:"Expert en Création de Manches Lourdes";s:1:"p";s:39:"Experts en Création de Manches Lourdes";s:11:"description";s:38:"Expert en Création de Manches Lourdes";}s:6:"scahve";a:3:{s:4:"name";s:34:"Expert en Création de Gilet Lourd";s:1:"p";s:35:"Experts en Création de Gilet Lourd";s:11:"description";s:34:"Expert en Création de Gilet Lourd";}s:6:"scalbe";a:3:{s:4:"name";s:39:"Expert en Création de Bottes Légères";s:1:"p";s:40:"Experts en Création de Bottes Légères";s:11:"description";s:39:"Expert en Création de Bottes Légères";}s:6:"scalge";a:3:{s:4:"name";s:36:"Expert en Création de Gants Légers";s:1:"p";s:37:"Experts en Création de Gants Légers";s:11:"description";s:36:"Expert en Création de Gants Légers";}s:6:"scalpe";a:3:{s:4:"name";s:38:"Expert en Création de Pantalon Léger";s:1:"p";s:39:"Experts en Création de Pantalon Léger";s:11:"description";s:38:"Expert en Création de Pantalon Léger";}s:6:"scalse";a:3:{s:4:"name";s:40:"Expert en Création de Manches Légères";s:1:"p";s:41:"Experts en Création de Manches Légères";s:11:"description";s:40:"Expert en Création de Manches Légères";}s:6:"scalve";a:3:{s:4:"name";s:35:"Expert en Création de Gilet Léger";s:1:"p";s:36:"Experts en Création de Gilet Léger";s:11:"description";s:35:"Expert en Création de Gilet Léger";}s:6:"scambe";a:3:{s:4:"name";s:38:"Expert en Création de Bottes Moyennes";s:1:"p";s:39:"Experts en Création de Bottes Moyennes";s:11:"description";s:38:"Expert en Création de Bottes Moyennes";}s:6:"scamge";a:3:{s:4:"name";s:35:"Expert en Création de Gants Moyens";s:1:"p";s:36:"Experts en Création de Gants Moyens";s:11:"description";s:35:"Expert en Création de Gants Moyens";}s:6:"scampe";a:3:{s:4:"name";s:37:"Expert en Création de Pantalon Moyen";s:1:"p";s:38:"Experts en Création de Pantalon Moyen";s:11:"description";s:37:"Expert en Création de Pantalon Moyen";}s:6:"scamse";a:3:{s:4:"name";s:39:"Expert en Création de Manches Moyennes";s:1:"p";s:40:"Experts en Création de Manches Moyennes";s:11:"description";s:39:"Expert en Création de Manches Moyennes";}s:6:"scamve";a:3:{s:4:"name";s:34:"Expert en Création de Gilet Moyen";s:1:"p";s:35:"Experts en Création de Gilet Moyen";s:11:"description";s:34:"Expert en Création de Gilet Moyen";}s:6:"scasse";a:3:{s:4:"name";s:31:"Expert en Création de Bouclier";s:1:"p";s:32:"Experts en Création de Bouclier";s:11:"description";s:31:"Expert en Création de Bouclier";}s:6:"scasbe";a:3:{s:4:"name";s:31:"Expert en Création de Rondache";s:1:"p";s:32:"Experts en Création de Rondache";s:11:"description";s:31:"Expert en Création de Rondache";}s:6:"scjaae";a:3:{s:4:"name";s:40:"Expert en Création d\'Anneau de Cheville";s:1:"p";s:41:"Experts en Création d\'Anneau de Cheville";s:11:"description";s:40:"Expert en Création d\'Anneau de Cheville";}s:6:"scjbae";a:3:{s:4:"name";s:31:"Expert en Création de Bracelet";s:1:"p";s:32:"Experts en Création de Bracelet";s:11:"description";s:31:"Expert en Création de Bracelet";}s:6:"scjdae";a:3:{s:4:"name";s:31:"Expert en Création de Diadème";s:1:"p";s:32:"Experts en Création de Diadème";s:11:"description";s:31:"Expert en Création de Diadème";}s:6:"scjeae";a:3:{s:4:"name";s:39:"Expert en Création de Boucle d\'Oreille";s:1:"p";s:40:"Experts en Création de Boucle d\'Oreille";s:11:"description";s:39:"Expert en Création de Boucle d\'Oreille";}s:6:"scjpae";a:3:{s:4:"name";s:32:"Expert en Création de Pendentif";s:1:"p";s:33:"Experts en Création de Pendentif";s:11:"description";s:32:"Expert en Création de Pendentif";}s:6:"scjrae";a:3:{s:4:"name";s:28:"Expert en Création de Bague";s:1:"p";s:29:"Experts en Création de Bague";s:11:"description";s:28:"Expert en Création de Bague";}s:6:"scm1me";a:3:{s:4:"name";s:29:"Expert en Création de Massue";s:1:"p";s:30:"Experts en Création de Massue";s:11:"description";s:29:"Expert en Création de Massue";}s:6:"scm1te";a:3:{s:4:"name";s:29:"Expert en Création de Bâton";s:1:"p";s:30:"Experts en Création de Bâton";s:11:"description";s:29:"Expert en Création de Bâton";}s:6:"scm1pe";a:3:{s:4:"name";s:28:"Expert en Création de Lance";s:1:"p";s:29:"Experts en Création de Lance";s:11:"description";s:28:"Expert en Création de Lance";}s:6:"scm1ae";a:3:{s:4:"name";s:28:"Expert en Création de Hache";s:1:"p";s:29:"Experts en Création de Hache";s:11:"description";s:28:"Expert en Création de Hache";}s:6:"scm1se";a:3:{s:4:"name";s:27:"Expert en Création d\'Epée";s:1:"p";s:28:"Experts en Création d\'Epée";s:11:"description";s:27:"Expert en Création d\'Epée";}s:6:"scm1de";a:3:{s:4:"name";s:28:"Expert en Création de Dague";s:1:"p";s:29:"Experts en Création de Dague";s:11:"description";s:28:"Expert en Création de Dague";}s:6:"scm2me";a:3:{s:4:"name";s:43:"Expert en Création de Massue à deux Mains";s:1:"p";s:44:"Experts en Création de Massue à deux Mains";s:11:"description";s:43:"Expert en Création de Massue à deux Mains";}s:6:"scm2pe";a:3:{s:4:"name";s:28:"Expert en Création de Pique";s:1:"p";s:29:"Experts en Création de Pique";s:11:"description";s:28:"Expert en Création de Pique";}s:6:"scm2ae";a:3:{s:4:"name";s:42:"Expert en Création de Hache à deux Mains";s:1:"p";s:43:"Experts en Création de Hache à deux Mains";s:11:"description";s:42:"Expert en Création de Hache à deux Mains";}s:6:"scm2se";a:3:{s:4:"name";s:41:"Expert en Création d\'Epée en deux Mains";s:1:"p";s:42:"Experts en Création d\'Epée en deux Mains";s:11:"description";s:41:"Expert en Création d\'Epée en deux Mains";}s:6:"scr1be";a:3:{s:4:"name";s:41:"Expert en Création de Pistolet-Arbalète";s:1:"p";s:42:"Experts en Création de Pistolet-Arbalète";s:11:"description";s:41:"Expert en Création de Pistolet-Arbalète";}s:6:"scr1pe";a:3:{s:4:"name";s:31:"Expert en Création de Pistolet";s:1:"p";s:32:"Experts en Création de Pistolet";s:11:"description";s:31:"Expert en Création de Pistolet";}s:6:"scr2ae";a:3:{s:4:"name";s:35:"Expert en Création de Mitrailleuse";s:1:"p";s:36:"Experts en Création de Mitrailleuse";s:11:"description";s:35:"Expert en Création de Mitrailleuse";}s:6:"scr2be";a:3:{s:4:"name";s:39:"Expert en Création de Fusil-Arbalète ";s:1:"p";s:40:"Experts en Création de Fusil-Arbalète ";s:11:"description";s:39:"Expert en Création de Fusil-Arbalète ";}s:6:"scr2le";a:3:{s:4:"name";s:37:"Expert en Création de Lance-Grenades";s:1:"p";s:38:"Experts en Création de Lance-Grenades";s:11:"description";s:37:"Expert en Création de Lance-Grenades";}s:6:"scr2re";a:3:{s:4:"name";s:29:"Expert en Création de Fusil ";s:1:"p";s:30:"Experts en Création de Fusil ";s:11:"description";s:29:"Expert en Création de Fusil ";}s:7:"scahbem";a:3:{s:4:"name";s:38:"Maître en Création de Bottes Lourdes";s:1:"p";s:39:"Maîtres en Création de Bottes Lourdes";s:11:"description";s:38:"Maître en Création de Bottes Lourdes";}s:7:"scahgem";a:3:{s:4:"name";s:36:"Maître en Création de Gants Lourds";s:1:"p";s:37:"Maîtres en Création de Gants Lourds";s:11:"description";s:36:"Maître en Création de Gants Lourds";}s:7:"scahhem";a:3:{s:4:"name";s:36:"Maître en Création de Casque Lourd";s:1:"p";s:37:"Maîtres en Création de Casque Lourd";s:11:"description";s:36:"Maître en Création de Casque Lourd";}s:7:"scahpem";a:3:{s:4:"name";s:38:"Maître en Création de Pantalon Lourd";s:1:"p";s:39:"Maîtres en Création de Pantalon Lourd";s:11:"description";s:38:"Maître en Création de Pantalon Lourd";}s:7:"scahsem";a:3:{s:4:"name";s:39:"Maître en Création de Manches Lourdes";s:1:"p";s:40:"Maîtres en Création de Manches Lourdes";s:11:"description";s:39:"Maître en Création de Manches Lourdes";}s:7:"scahvem";a:3:{s:4:"name";s:35:"Maître en Création de Gilet Lourd";s:1:"p";s:36:"Maîtres en Création de Gilet Lourd";s:11:"description";s:35:"Maître en Création de Gilet Lourd";}s:7:"scalbem";a:3:{s:4:"name";s:40:"Maître en Création de Bottes Légères";s:1:"p";s:41:"Maîtres en Création de Bottes Légères";s:11:"description";s:40:"Maître en Création de Bottes Légères";}s:7:"scalgem";a:3:{s:4:"name";s:37:"Maître en Création de Gants Légers";s:1:"p";s:38:"Maîtres en Création de Gants Légers";s:11:"description";s:37:"Maître en Création de Gants Légers";}s:7:"scalpem";a:3:{s:4:"name";s:39:"Maître en Création de Pantalon Léger";s:1:"p";s:40:"Maîtres en Création de Pantalon Léger";s:11:"description";s:39:"Maître en Création de Pantalon Léger";}s:7:"scalsem";a:3:{s:4:"name";s:41:"Maître en Création de manches Légères";s:1:"p";s:42:"Maîtres en Création de manches Légères";s:11:"description";s:41:"Maître en Création de manches Légères";}s:7:"scalvem";a:3:{s:4:"name";s:36:"Maître en Création de Gilet Léger";s:1:"p";s:37:"Maîtres en Création de Gilet Léger";s:11:"description";s:36:"Maître en Création de Gilet Léger";}s:7:"scambem";a:3:{s:4:"name";s:39:"Maître en Création de Bottes Moyennes";s:1:"p";s:40:"Maîtres en Création de Bottes Moyennes";s:11:"description";s:39:"Maître en Création de Bottes Moyennes";}s:7:"scamgem";a:3:{s:4:"name";s:36:"Maître en Création de Gants Moyens";s:1:"p";s:37:"Maîtres en Création de Gants Moyens";s:11:"description";s:36:"Maître en Création de Gants Moyens";}s:7:"scampem";a:3:{s:4:"name";s:39:"Maître en Création de Pantalon Moyens";s:1:"p";s:40:"Maîtres en Création de Pantalon Moyens";s:11:"description";s:39:"Maître en Création de Pantalon Moyens";}s:7:"scamsem";a:3:{s:4:"name";s:40:"Maître en Création de Manches Moyennes";s:1:"p";s:41:"Maîtres en Création de Manches Moyennes";s:11:"description";s:40:"Maître en Création de Manches Moyennes";}s:7:"scamvem";a:3:{s:4:"name";s:35:"Maître en Création de Gilet Moyen";s:1:"p";s:36:"Maîtres en Création de Gilet Moyen";s:11:"description";s:35:"Maître en Création de Gilet Moyen";}s:7:"scassem";a:3:{s:4:"name";s:32:"Maître en Création de Bouclier";s:1:"p";s:33:"Maîtres en Création de Bouclier";s:11:"description";s:32:"Maître en Création de Bouclier";}s:7:"scasbem";a:3:{s:4:"name";s:32:"Maître en Création de Rondache";s:1:"p";s:33:"Maîtres en Création de Rondache";s:11:"description";s:32:"Maître en Création de Rondache";}s:7:"scjaaem";a:3:{s:4:"name";s:41:"Maître en Création d\'Anneau de Cheville";s:1:"p";s:42:"Maîtres en Création d\'Anneau de Cheville";s:11:"description";s:41:"Maître en Création d\'Anneau de Cheville";}s:7:"scjbaem";a:3:{s:4:"name";s:32:"Maître en Création de Bracelet";s:1:"p";s:33:"Maîtres en Création de Bracelet";s:11:"description";s:32:"Maître en Création de Bracelet";}s:7:"scjdaem";a:3:{s:4:"name";s:32:"Maître en Création de Diadème";s:1:"p";s:33:"Maîtres en Création de Diadème";s:11:"description";s:32:"Maître en Création de Diadème";}s:7:"scjeaem";a:3:{s:4:"name";s:40:"Maître en Création de Boucle d\'Oreille";s:1:"p";s:41:"Maîtres en Création de Boucle d\'Oreille";s:11:"description";s:40:"Maître en Création de Boucle d\'Oreille";}s:7:"scjpaem";a:3:{s:4:"name";s:33:"Maître en Création de Pendentif";s:1:"p";s:34:"Maîtres en Création de Pendentif";s:11:"description";s:33:"Maître en Création de Pendentif";}s:7:"scjraem";a:3:{s:4:"name";s:29:"Maître en Création de Bague";s:1:"p";s:30:"Maîtres en Création de Bague";s:11:"description";s:29:"Maître en Création de Bague";}s:7:"scm1mem";a:3:{s:4:"name";s:30:"Maître en Création de Massue";s:1:"p";s:31:"Maîtres en Création de Massue";s:11:"description";s:30:"Maître en Création de Massue";}s:7:"scm1tem";a:3:{s:4:"name";s:30:"Maître en Création de Bâton";s:1:"p";s:31:"Maîtres en Création de Bâton";s:11:"description";s:30:"Maître en Création de Bâton";}s:7:"scm1pem";a:3:{s:4:"name";s:29:"Maître en Création de Lance";s:1:"p";s:30:"Maîtres en Création de Lance";s:11:"description";s:29:"Maître en Création de Lance";}s:7:"scm1aem";a:3:{s:4:"name";s:29:"Maître en Création de Hache";s:1:"p";s:30:"Maîtres en Création de Hache";s:11:"description";s:29:"Maître en Création de Hache";}s:7:"scm1sem";a:3:{s:4:"name";s:28:"Maître en Création d\'Epée";s:1:"p";s:29:"Maîtres en Création d\'Epée";s:11:"description";s:28:"Maître en Création d\'Epée";}s:7:"scm1dem";a:3:{s:4:"name";s:29:"Maître en Création de Dague";s:1:"p";s:30:"Maîtres en Création de Dague";s:11:"description";s:29:"Maître en Création de Dague";}s:7:"scm2mem";a:3:{s:4:"name";s:44:"Maître en Création de Massue à deux Mains";s:1:"p";s:45:"Maîtres en Création de Massue à deux Mains";s:11:"description";s:44:"Maître en Création de Massue à deux Mains";}s:7:"scm2pem";a:3:{s:4:"name";s:29:"Maître en Création de Pique";s:1:"p";s:30:"Maîtres en Création de Pique";s:11:"description";s:29:"Maître en Création de Pique";}s:7:"scm2aem";a:3:{s:4:"name";s:43:"Maître en Création de Hache à deux Mains";s:1:"p";s:44:"Maîtres en Création de Hache à deux Mains";s:11:"description";s:43:"Maître en Création de Hache à deux Mains";}s:7:"scm2sem";a:3:{s:4:"name";s:42:"Maître en Création d\'Epée en deux Mains";s:1:"p";s:43:"Maîtres en Création d\'Epée en deux Mains";s:11:"description";s:42:"Maître en Création d\'Epée en deux Mains";}s:7:"scr1bem";a:3:{s:4:"name";s:42:"Maître en Création de Pistolet-Arbalète";s:1:"p";s:43:"Maîtres en Création de Pistolet-Arbalète";s:11:"description";s:42:"Maître en Création de Pistolet-Arbalète";}s:7:"scr1pem";a:3:{s:4:"name";s:32:"Maître en Création de Pistolet";s:1:"p";s:33:"Maîtres en Création de Pistolet";s:11:"description";s:32:"Maître en Création de Pistolet";}s:7:"scr2aem";a:3:{s:4:"name";s:36:"Maître en Création de Mitrailleuse";s:1:"p";s:37:"Maîtres en Création de Mitrailleuse";s:11:"description";s:36:"Maître en Création de Mitrailleuse";}s:7:"scr2bem";a:3:{s:4:"name";s:40:"Maître en Création de Fusil-Arbalète ";s:1:"p";s:41:"Maîtres en Création de Fusil-Arbalète ";s:11:"description";s:40:"Maître en Création de Fusil-Arbalète ";}s:7:"scr2lem";a:3:{s:4:"name";s:38:"Maître en Création de Lance-Grenades";s:1:"p";s:39:"Maîtres en Création de Lance-Grenades";s:11:"description";s:38:"Maître en Création de Lance-Grenades";}s:7:"scr2rem";a:3:{s:4:"name";s:30:"Maître en Création de Fusil ";s:1:"p";s:31:"Maîtres en Création de Fusil ";s:11:"description";s:30:"Maître en Création de Fusil ";}s:4:"scmc";a:3:{s:4:"name";s:33:"Créer Armes de Combat Rapproché";s:1:"p";s:33:"Créer Armes de Combat Rapproché";s:11:"description";s:33:"Créer Armes de Combat Rapproché";}s:5:"scmca";a:3:{s:4:"name";s:49:"Initié en Création d\'Armes de Combat Rapproché";s:1:"p";s:50:"Initiés en Création d\'Armes de Combat Rapproché";s:11:"description";s:49:"Initié en Création d\'Armes de Combat Rapproché";}s:6:"scmcae";a:3:{s:4:"name";s:48:"Expert en Création d\'Armes de Combat Rapproché";s:1:"p";s:49:"Experts en Création d\'Armes de Combat Rapproché";s:11:"description";s:48:"Expert en Création d\'Armes de Combat Rapproché";}s:7:"scmcaem";a:3:{s:4:"name";s:49:"Maître en Création d\'Armes de Combat Rapproché";s:1:"p";s:50:"Maîtres en Création d\'Armes de Combat Rapproché";s:11:"description";s:49:"Maître en Création d\'Armes de Combat Rapproché";}}';
	$traduction = unserialize($ligne);
	return $traduction[$index]['name'];
}

?>