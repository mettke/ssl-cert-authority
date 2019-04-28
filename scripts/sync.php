#!/usr/bin/env php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . 'scripts/phpseclib');
chdir(__DIR__);
require('../core.php');
require('sync-common.php');
include('Crypt/RSA.php');
require('Net/SSH2.php');
require('Net/SCP.php');

$required_files = array('config/cert-sync', 'config/cert-sync.pub');
foreach($required_files as $file) {
	if(!file_exists($file)) die("Sync cannot start - $file not found.\n");
}

// Parse the command-line arguments
$options = getopt('h:i:au:p', array('help', 'host:', 'id:', 'all'));
if(isset($options['help'])) {
	show_help();
	exit(0);
}
$short_to_long = array(
	'h' => 'host',
	'i' => 'id',
	'a' => 'all'
);
foreach($short_to_long as $short => $long) {
	if(isset($options[$short]) && isset($options[$long])) {
		echo "Error: short form -$short and long form --$long both specified\n";
		show_help();
		exit(1);
	}
	if(isset($options[$short])) $options[$long] = $options[$short];
}
$hostopts = 0;
if(isset($options['host'])) $hostopts++;
if(isset($options['id'])) $hostopts++;
if(isset($options['all'])) $hostopts++;
if($hostopts != 1) {
	echo "Error: must specify exactly one of --host, --id, or --all\n";
	show_help();
	exit(1);
}

// Use 'cert-sync' user as the active user (create if it does not yet exist)
try {
	$active_user = $user_dir->get_user_by_uid('cert-sync');
} catch(UserNotFoundException $e) {
	$active_user = new User;
	$active_user->uid = 'cert-sync';
	$active_user->name = 'Synchronization script';
	$active_user->email = '';
	$active_user->auth_realm = 'local';
	$active_user->active = 1;
	$active_user->admin = 1;
	$active_user->developer = 0;
	$user_dir->add_user($active_user);
}


// Build list of servers to sync
if(isset($options['all'])) {
	$servers = $server_dir->list_servers();
} elseif(isset($options['host'])) {
	$servers = array();
	$hostnames = explode(",", $options['host']);
	foreach($hostnames as $hostname) {
		$hostname = trim($hostname);
		try {
			$servers[] = $server_dir->get_server_by_hostname($hostname);
		} catch(ServerNotFoundException $e) {
			echo "Error: hostname '$hostname' not found\n";
			exit(1);
		}
	}
} elseif(isset($options['id'])) {
	sync_server($options['id']);
	exit(0);
}

$pending_syncs = array();
foreach($servers as $server) {
	$pending_syncs[$server->hostname] = $server;
}

$sync_procs = array();
define('MAX_PROCS', 1);
while(count($sync_procs) > 0 || count($pending_syncs) > 0) {
	while(count($sync_procs) < MAX_PROCS && count($pending_syncs) > 0) {
		$server = reset($pending_syncs);
		$hostname = key($pending_syncs);
		$args = array();
		$args[] = '--id';
		$args[] = $server->id;
		$sync_procs[] = new SyncProcess(__FILE__, $args);
		unset($pending_syncs[$hostname]);
	}
	foreach($sync_procs as $ref => $sync_proc) {
		$data = $sync_proc->get_data();
		if(!empty($data)) {
			echo $data['output'];
			unset($sync_procs[$ref]);
		}
	}
	usleep(200000);
}

function show_help() {
?>
Usage: sync.php [OPTIONS]
Syncs certificates to the specified hosts and restarts dependent services.

Mandatory arguments to long options are mandatory for short options too.
  -a, --all              sync with all active hosts in the database
  -h, --host=HOSTNAME    sync only the specified host(s)
                         (specified by name, comma-separated)
  -i, --id=ID            sync only the specified single host
                         (specified by id)
      --help             display this help and exit
<?php
}

function sync_server($id) {
	global $config;
	global $server_dir;

	$certdir = '/var/local/cert-sync';

	$server = $server_dir->get_server_by_id($id);
	$hostname = $server->hostname;
	echo date('c')." {$hostname}: Preparing sync.\n";
	$server->ip_address = gethostbyname($hostname);
	$server->update();

	// IP address check
	echo date('c')." {$hostname}: Checking IP address {$server->ip_address}.\n";
	$matching_servers = $server_dir->list_servers(array(), array('ip_address' => $server->ip_address));
	if(count($matching_servers) > 1) {
		echo date('c')." {$hostname}: Multiple hosts with same IP address.\n";
		$server->sync_report('sync failure', 'Multiple hosts with same IP address');
		$server->delete_all_sync_requests();
		return;
	}

	echo date('c')." {$hostname}: Attempting to connect.\n";
	try {
		$ssh = new Net_SSH2($hostname, $server->port);
		$scp = new Net_SCP($ssh);
		$ssh->enableQuietMode();
		$ssh->_connect();
	} catch(Exception $e) {
		echo date('c')." {$hostname}: Failed to connect.\n".$e;
		$server->sync_report('sync failure', 'SSH connection failed');
		$server->delete_all_sync_requests();
		return;
	}

	try {
		$hostkey = substr($ssh->getServerPublicHostKey(), 8);
		$hostkey = explode(' ', $hostkey, 2)[1];
		$hostkey = md5(base64_decode($hostkey));
		$hostkey = implode(':', str_split($hostkey, 2));
		$fingerprint = $hostkey;
	} catch(Exception $e) {
		echo date('c')." {$hostname}: Unable to parse host key.\n";
		$server->sync_report('sync failure', 'SSH host key not supported');
		$server->delete_all_sync_requests();
		return;
	}
	
	if(is_null($server->rsa_key_fingerprint)) {
		$server->rsa_key_fingerprint = $fingerprint;
		$server->update();
	} else {
		if(strcmp($server->rsa_key_fingerprint, $fingerprint) !== 0) {
			echo date('c')." {$hostname}: RSA key validation failed.\n";
			$server->sync_report('sync failure', 'SSH host key verification failed');
			$server->delete_all_sync_requests();
			return;
		}
	}
	if(!isset($config['security']) || !isset($config['security']['host_key_collision_protection']) || $config['security']['host_key_collision_protection'] == 1) {
		$matching_servers = $server_dir->list_servers(array(), array('rsa_key_fingerprint' => $server->rsa_key_fingerprint));
		if(count($matching_servers) > 1) {
			echo date('c')." {$hostname}: Multiple hosts with same host key.\n";
			$server->sync_report('sync failure', 'Multiple hosts with same host key');
			$server->delete_all_sync_requests();
			return;
		}
	}
	try {
		$success = false;
		$key = new Crypt_RSA();
		$key->loadKey(file_get_contents('config/cert-sync'));
		$success = $ssh->login('cert-sync', $key);
	} catch(ErrorException $e) {}
	if($success) {
		echo date('c')." {$hostname}: Logged in.\n";
	} else {
		echo date('c')." {$hostname}: Public key authentication failed.\n";
		$server->sync_report('sync failure', 'SSH authentication failed');
		$server->delete_all_sync_requests();
		return;
	}

	$ssh->exec('/usr/bin/env test -d ' . $certdir);
	if(is_bool($ssh->getExitStatus()) || $ssh->getExitStatus() != 0) {
		echo date('c')." {$hostname}: Cert directory does not exist.\n";
		$server->sync_report('sync failure', 'Cert directory does not exist');
		$server->delete_all_sync_requests();
	}

	// From this point on, catch SIGTERM and ignore. SIGINT or SIGKILL is required to stop, so timeout wrapper won't
	// cause a partial sync
	pcntl_signal(SIGTERM, SIG_IGN);

	// $account_errors = 0;
	$cleanup_errors = 0;

	if(isset($config['security']) && isset($config['security']['hostname_verification']) && $config['security']['hostname_verification'] >= 1) {
		// Verify that we have mutual agreement with the server that we sync to it with this hostname
		$allowed_hostnames = null;
		if($config['security']['hostname_verification'] >= 2) {
			// 2+ = Compare with /var/local/cert-sync/.hostnames
			$allowed_hostnames = explode("\n", trim($ssh->exec('/usr/bin/env cat /var/local/cert-sync/.hostnames')));
			echo implode("|",$allowed_hostnames);
			if(is_bool($ssh->getExitStatus()) || $ssh->getExitStatus() != 0) {
				if($config['security']['hostname_verification'] >= 3) {
					// 3+ = Abort if file does not exist
					echo date('c')." {$hostname}: Hostnames file missing.\n";
					$server->sync_report('sync failure', 'Hostnames file missing');
					$server->delete_all_sync_requests();
					return;
				} else {
					$allowed_hostnames = null;
				}
			}
		}
		if(is_null($allowed_hostnames)) {
			try {
				$allowed_hostnames = array(trim($ssh->exec('/usr/bin/env hostname -f')));
				echo implode("|",$allowed_hostnames);
			} catch(ErrorException $e) {
				echo date('c')." {$hostname}: Cannot execute hostname -f.\n";
				$server->sync_report('sync failure', 'Cannot execute hostname -f');
				$server->delete_all_sync_requests();
				return;
			}
		}
		if(!in_array($hostname, $allowed_hostnames)) {
			echo date('c')." {$hostname}: Hostname check failed (allowed: ".implode(", ", $allowed_hostnames).").\n";
			$server->sync_report('sync failure', 'Hostname check failed');
			$server->delete_all_sync_requests();
			return;
		}
	}

	try {
		$output = trim($ssh->exec('/usr/bin/env cat /etc/uuid'));
		if(!is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0) {
			$server->uuid = $output;
			$server->update();
		}
	} catch(ErrorException $e) {
		// If the /etc/uuid file does not exist, silently ignore
	}

	// New sync
	$server_profiles = $server->list_dependent_profiles();
	$certificates = array();
	$profiles = array();
	$services = array();
	$variables = array();
	$scripts = array();
	foreach($server_profiles as $profile) {
		$certificate = $profile->certificate;
		$certificates[$certificate->name] = $certificate;
		$profiles[$profile->name] = $certificate->name;

		foreach($profile->list_services() as $service) {
			$service->cert_serial = $certificate->serial;
			$services[$service->name] = $service;

			$vars = array_map(function($variable) {
				return "export ".escapeshellcmd($variable->name)."=".escapeshellarg($variable->value);
			},$service->list_variables());

			$variables[$service->name] = implode('\n', $vars);

			$restart_script = $service->restart_script;
			if($restart_script->name != "") {
				$scripts[$restart_script->name] = $snip = str_replace("\r", '', $restart_script->content);
			}
			$status_script = $service->status_script;
			if($status_script->name != "") {
				$scripts[$status_script->name] = str_replace("\r", '', $status_script->content);
			}
			$check_script = $service->check_script;
			if($check_script->name != "") {
				$scripts[$check_script->name] = str_replace("\r", '', $check_script->content);
			}
		}
	}

	$certificate_errors = 0;
	$fullchain_errors = 0;
	$private_errors = 0;
	$script_errors = 0;
	$profile_errors = 0;
	$cleanup_errors = 0;

	list($certificate_errors, $cleanup_errors) = file_sync($ssh, $scp, $hostname, $server, $certdir, "cert", array_map(function ($certificate) {
		return $certificate->cert;
	}, $certificates));
	if($certificate_errors != 0) {
		$server->sync_report('sync failure', $certificate_errors.' certificates'.($certificate_errors == 1 ? '' : 's').' failed to sync');
		return;
	}
	if($cleanup_errors == 0) {
		list($fullchain_errors, $cleanup_errors) = file_sync($ssh, $scp, $hostname, $server, $certdir, "fullchain", array_map(function ($certificate) {
			return $certificate->fullchain;
		}, $certificates));
	}
	if($fullchain_errors != 0) {
		$server->sync_report('sync failure', $fullchain_errors.' fullchain certificates'.($fullchain_errors == 1 ? '' : 's').' failed to sync');
		return;
	}
	if($cleanup_errors == 0) {
		list($private_errors, $cleanup_errors) = file_sync($ssh, $scp, $hostname, $server, $certdir, "private", array_map(function ($certificate) {
			return $certificate->private;
		}, $certificates));
	}
	if($private_errors != 0) {
		$server->sync_report('sync failure', $private_errors.' private keys'.($private_errors == 1 ? '' : 's').' failed to sync');
		return;
	}
	if($private_errors == 0 && $cleanup_errors == 0) {
		list($script_errors, $cleanup_errors) = file_sync($ssh, $scp, $hostname, $server, $certdir, "script", $scripts);
	}
	if($script_errors != 0) {
		$server->sync_report('sync failure', $script_errors.' script'.($script_errors == 1 ? '' : 's').' failed to sync');
		return;			
	}
	if($cleanup_errors == 0) {
		list($variable_error, $cleanup_errors) = variable_sync($ssh, $scp, $hostname, $server, $certdir, $variables);
	}
	if($variable_error != 0) {
		$server->sync_report('sync failure', $profile_errors.' variable'.($profile_errors == 1 ? '' : 's').' failed to sync');
		return;
	}
	if($cleanup_errors == 0) {
		list($profile_errors, $cleanup_errors) = profile_sync($ssh, $hostname, $server, $certdir, $profiles);
	}
	if($profile_errors != 0) {
		$server->sync_report('sync failure', $profile_errors.' profile'.($profile_errors == 1 ? '' : 's').' failed to sync');
		return;
	}
	if($cleanup_errors == 0) {
		restart_services($ssh, $hostname, $server, $certdir, $services);
	}
	
	if($cleanup_errors > 0) {
		$server->sync_report('sync failure', 'Failed to clean up '.$cleanup_errors.' file'.($cleanup_errors == 1 ? '' : 's'));
	} else {
		$server->sync_report('sync success', 'Synced successfully');
	}
	echo date('c')." {$hostname}: Sync finished\n";
}

function file_sync($ssh, $scp, $hostname, $server, $certdir, $folder, $files) {
	$ssh->exec('/usr/bin/env test -d ' . $certdir . "/$folder");
	if(is_bool($ssh->getExitStatus()) || $ssh->getExitStatus() != 0) {			
		try {		
			$success = false;			
			$entries = explode("\n", $ssh->exec('/usr/bin/env mkdir '.escapeshellarg($certdir).'/'.$folder));
			$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Cannot execute mkdir.\n";
			$server->sync_report('sync failure', 'Cannot execute mkdir');
			$server->delete_all_sync_requests();
			exit(0);
		}
	}

	try {		
		$success = false;			
		$entries = explode("\n", $ssh->exec('/usr/bin/env sha1sum '.escapeshellarg($certdir).'/'.$folder.'/*'));
		$sha1sums = array();
		if(!is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0) {
			foreach($entries as $entry) {
				if(preg_match('|^([0-9a-f]{40})  '.preg_quote($certdir, '|').'/'.$folder.'/(.*)$|', $entry, $matches)) {
					$sha1sums[$matches[2]] = $matches[1];
				}
			}
			$success = true;
		} elseif($ssh->getExitStatus() == 1) {
			// No files in directory
			$success = true;
		}
	} catch(ErrorException $e) {}
	if(!$success) {
		echo date('c')." {$hostname}: Cannot execute sha1sum.\n";
		$server->sync_report('sync failure', 'Cannot execute sha1sum');
		$server->delete_all_sync_requests();
		exit(0);
	}

	$file_errors = 0;
	$cleanup_errors = 0;
	foreach($files as $name => $content) {
		try {
			$remote_filename = "$certdir/$folder/$name";
			if(isset($sha1sums[$name]) && $sha1sums[$name] == sha1($content)) {
				echo date('c')." {$hostname}: No changes required for {$name}\n";
			} else {
				$local_filename = tempnam('/tmp', 'syncfile');
				$fh = fopen($local_filename, 'w');
				fwrite($fh, $content);
				fclose($fh);
				$success = $scp->put($remote_filename, $local_filename, NET_SCP_LOCAL_FILE);
				if(!$success) {
					echo date('c')." {$hostname}: Unable to transfer file using scp\n";
				}
				if($success) {
					$ssh->exec('/usr/bin/env chmod 600 '.escapeshellarg($remote_filename));
					$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
					if(!$success) {
						echo date('c')." {$hostname}: Unable to change permission\n";
					}
				}
				if($success) {
					$ssh->exec('/usr/bin/env chown cert-sync: '.escapeshellarg($remote_filename));
					$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
					if(!$success) {
						echo date('c')." {$hostname}: Unable to change ownership\n";
					}
				}
				if($success) {
					unlink($local_filename);
					echo date('c')." {$hostname}: Updated {$name}\n";
				}
			}
			unset($sha1sums[$name]);
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Sync command execution failed for $name.\n";
			$file_errors++;
		}
	}
	// Clean up directory
	foreach($sha1sums as $file => $sha1sum) {
		if($file != '') {
			try {
				$remote_filename = "$certdir/$folder/$file";
				$success = false;
				$ssh->exec('/usr/bin/env rm -f '.escapeshellarg($remote_filename));
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
			} catch(ErrorException $e) {}
			if($success) {
				echo date('c')." {$hostname}: Removed unknown file: {$file}\n";
			} else {
				$cleanup_errors++;
				echo date('c')." {$hostname}: Couldn't remove unknown file: {$file}.\n";
			}
		}
	}
	return array($file_errors, $cleanup_errors);
}

function variable_sync($ssh, $scp, $hostname, $server, $certdir, $variables) {
	$ssh->exec('/usr/bin/env test -d ' . $certdir . "/variable");
	if(is_bool($ssh->getExitStatus()) || $ssh->getExitStatus() != 0) {			
		try {		
			$success = false;			
			$ssh->exec('/usr/bin/env mkdir '.escapeshellarg($certdir).'/variable');
			$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Cannot execute mkdir.\n";
			$server->sync_report('sync failure', 'Cannot execute mkdir');
			$server->delete_all_sync_requests();
			exit(0);
		}
	}

	try {		
		$success = false;			
		$entries = explode("\n", $ssh->exec('/usr/bin/env sha1sum '.escapeshellarg($certdir).'/variable/*'));
		$sha1sums = array();
		if(!is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0) {
			foreach($entries as $entry) {
				if(preg_match('|^([0-9a-f]{40})  '.preg_quote($certdir, '|').'/variable/(.*)$|', $entry, $matches)) {
					$sha1sums[$matches[2]] = $matches[1];
				}
			}
			$success = true;
		} elseif($ssh->getExitStatus() == 1) {
			// No files in directory
			$success = true;
		}
	} catch(ErrorException $e) {}
	if(!$success) {
		echo date('c')." {$hostname}: Cannot execute sha1sum.\n";
		$server->sync_report('sync failure', 'Cannot execute sha1sum');
		$server->delete_all_sync_requests();
		exit(0);
	}

	$file_errors = 0;
	$cleanup_errors = 0;
	foreach($variables as $name => $content) {
		try {
			$remote_filename = "$certdir/variable/$name";
			if(isset($sha1sums[$name]) && $sha1sums[$name] == sha1($content)) {
				echo date('c')." {$hostname}: No changes required for {$name}\n";
			} else {
				$local_filename = tempnam('/tmp', 'syncfile');
				$fh = fopen($local_filename, 'w');
				fwrite($fh, $content);
				fclose($fh);
				$success = $scp->put($remote_filename, $local_filename, NET_SCP_LOCAL_FILE);
				if(!$success) {
					echo date('c')." {$hostname}: Unable to transfer file using scp\n";
				}
				if($success) {
					$ssh->exec('/usr/bin/env chmod 600 '.escapeshellarg($remote_filename));
					$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
					if(!$success) {
						echo date('c')." {$hostname}: Unable to change permission\n";
					}
				}
				if($success) {
					$ssh->exec('/usr/bin/env chown cert-sync: '.escapeshellarg($remote_filename));
					$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
					if(!$success) {
						echo date('c')." {$hostname}: Unable to change ownership\n";
					}
				}
				if($success) {
					unlink($local_filename);
					echo date('c')." {$hostname}: Updated {$name}\n";
				}
			}
			unset($sha1sums[$name]);
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Sync command execution failed for $name.\n";
			$file_errors++;
		}
	}
	// Clean up directory
	foreach($sha1sums as $file => $sha1sum) {
		if($file != '') {
			try {
				$remote_filename = "$certdir/variable/$file";
				$success = false;
				$ssh->exec('/usr/bin/env rm -f '.escapeshellarg($remote_filename));
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
			} catch(ErrorException $e) {}
			if($success) {
				echo date('c')." {$hostname}: Removed unknown file: {$file}\n";
			} else {
				$cleanup_errors++;
				echo date('c')." {$hostname}: Couldn't remove unknown file: {$file}.\n";
			}
		}
	}
	return array($file_errors, $cleanup_errors);
}

function profile_sync($ssh, $hostname, $server, $certdir, $profiles) {
	$ssh->exec('/usr/bin/env test -d ' . $certdir . "/profile");
	if(is_bool($ssh->getExitStatus()) || $ssh->getExitStatus() != 0) {			
		try {		
			$success = false;			
			$ssh->exec('/usr/bin/env mkdir '.escapeshellarg($certdir).'/profile');
			$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Cannot execute mkdir.\n";
			$server->sync_report('sync failure', 'Cannot execute mkdir');
			$server->delete_all_sync_requests();
			exit(0);
		}
	}

	try {		
		$success = false;			
		$entries = explode("\n", $ssh->exec('/usr/bin/env ls -1 '.escapeshellarg($certdir).'/profile'));
		$files = array();
		if(!is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0) {
			foreach($entries as $entry) {
				$files[$entry] = $entry;
			}
			$success = true;
		}
	} catch(ErrorException $e) {}
	if(!$success) {
		echo date('c')." {$hostname}: Cannot execute ls.\n";
		$server->sync_report('sync failure', 'Cannot execute ls');
		$server->delete_all_sync_requests();
		exit(0);
	}

	$file_errors = 0;
	$cleanup_errors = 0;
	foreach($profiles as $profile_name => $cert_name) {
		$link_filename = "$certdir/profile/$profile_name";
		try {		
			$success = false;			
			$ssh->exec('/usr/bin/env mkdir -p '.escapeshellarg($link_filename));
			$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;

			foreach(array("cert", "fullchain", "private") as $type) {
				$link_dest_filename = "../../$type/$cert_name";
				if($success) {
					$ssh->exec('/usr/bin/env ln -sf '.escapeshellarg($link_dest_filename).' '.escapeshellarg($link_filename."/$type"));
					$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
				}
			}
			unset($files[$profile_name]);
		} catch(ErrorException $e) {}
		if(!$success) {
			echo date('c')." {$hostname}: Sync command execution failed for $profile_name.\n";
			$file_errors++;
		} else {
			echo date('c')." {$hostname}: Updated profile {$profile_name}\n";
		}
	}
	// Clean up directory
	foreach($files as $file => $file) {
		if($file != '') {
			try {
				$remote_filename = "$certdir/profile/$file";
				$success = false;
				$ssh->exec('/usr/bin/env rm -rf '.escapeshellarg($remote_filename));
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
			} catch(ErrorException $e) {}
			if($success) {
				echo date('c')." {$hostname}: Removed unknown file: {$file}\n";
			} else {
				$cleanup_errors++;
				echo date('c')." {$hostname}: Couldn't remove unknown file: {$file}.\n";
			}
		}
	}
	return array($file_errors, $cleanup_errors);
}

function restart_services($ssh, $hostname, $server, $certdir, $services) {
	foreach($services as $service) {
		echo date('c')." {$hostname}: Restarting service {$service->name}\n";

		$restart_script = $service->restart_script;
		if($restart_script->name != "") {
			try {		
				$success = false;
				$ssh->exec('/usr/bin/env sh -c ". '.escapeshellarg("$certdir/variable/$service->name").'; sh '.escapeshellarg("$certdir/script/$restart_script->name").'"');
				$err = $ssh->getStdError();
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
				if($err != "") {
					echo date('c')." {$hostname}: restart script returned: $err\n";
				}
			} catch(ErrorException $e) {}
			if(!$success) {
				echo date('c')." {$hostname}: Cannot execute restart script of $service->name.\n";
				$server->sync_report('sync failure', 'Failed while executing restart script of '.$service->name);
				$server->delete_all_sync_requests();
				exit(0);
			}
			sleep(1);
		}
		
		$status_script = $service->status_script;
		if($status_script->name != "") {
			try {		
				$success = false;			
				$ssh->exec('/usr/bin/env sh -c ". '.escapeshellarg("$certdir/variable/$service->name").'; sh '.escapeshellarg("$certdir/script/$status_script->name").'"');
				$err = $ssh->getStdError();
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
				if($err != "") {
					echo date('c')." {$hostname}: stayus script returned: $err\n";
				}
			} catch(ErrorException $e) {}
			if(!$success) {
				echo date('c')." {$hostname}: Cannot execute status script of $service->name.\n";
				$server->sync_report('sync failure', 'Failed while executing status script of '.$service->name);
				$server->delete_all_sync_requests();
				exit(0);
			}
		}

		$check_script = $service->check_script;
		if($check_script->name != "") {
			try {		
				$success = false;			
				$ssh->exec('/usr/bin/env sh -c ". '.escapeshellarg("$certdir/variable/$service->name").'; sh '.escapeshellarg("$certdir/script/$check_script->name").'"');
				$err = $ssh->getStdError();
				$success = !is_bool($ssh->getExitStatus()) && $ssh->getExitStatus() == 0;
				if($err != "") {
					echo date('c')." {$hostname}: check script returned: $err\n";
				}
				if($result != $service->cert_serial) {
					echo date('c')." {$hostname}: Check script returned incorrect certificate serial: $result\n";
					$server->sync_report('sync failure', 'Check script returned incorrect certificate serial');
					$server->delete_all_sync_requests();
					exit(0);
				}
			} catch(ErrorException $e) {}
			if(!$success) {
				echo date('c')." {$hostname}: Cannot execute check script of $service->name.\n";
				$server->sync_report('sync failure', 'Cannot execute check script of '.$service->name);
				$server->delete_all_sync_requests();
				exit(0);
			}			
		}
	}
}
