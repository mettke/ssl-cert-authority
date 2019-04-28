<?php
$admin_mail = $this->get('admin_mail');
$baseurl = $this->get('baseurl');
$security_config = $this->get('security_config');
?>
<div class="panel-group" id="help">
	<h1>Help</h1>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">
				<a data-toggle="collapse" data-parent="#help" href="#concepts">
					Concepts
				</a>
			</h2>
		</div>
		<div id="concepts" class="panel-collapse collapse">
			<div class="panel-body">
				<h3>Iconography</h3>
				<p>Most objects that are known by SSL Cert Authority are represented by icons:</p>
				<h4><span class="glyphicon glyphicon-user"></span> Users</h4>
				<p>Users of SSL Cert Authority.</p>
				<h4><span class="glyphicon glyphicon-file"></span> Scripts</h4>
				<p>Scripts allow the restart of services and the verification of the certificate deployment</p>
				<h4><span class="glyphicon glyphicon-cloud"></span> Services</h4>
				<p>Services represent programs which require a certificate.</p>
				<h4><span class="glyphicon glyphicon-console"></span> Variables</h4>
				<p>Variables allow a generic script to be used for multiple Services.</p>
				<h4><span class="glyphicon glyphicon-certificate"></span> Certificates</h4>
				<p>Certificates for the deployment.</p>
				<h4><span class="glyphicon glyphicon-hdd"></span> Servers</h4>
				<p>Physical or virtual servers.</p>
				<h4><span class="glyphicon glyphicon-book"></span> Profiles</h4>
				<p>Profiles bind certificates, services and servers together.</p>
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title">
				<a data-toggle="collapse" data-parent="#help" href="#add_server">
					Adding a server to SSL Cert Authority
				</a>
			</h2>
		</div>
		<div id="add_server" class="panel-collapse collapse">
			<div class="panel-body">
				<p>Contact <a href="mailto:<?php out($admin_mail)?>"><?php out($admin_mail)?></a> to have your server(s) added to SSL Cert Authority.</p>
			</div>
		</div>
	</div>
	<h2>Frequently asked questions</h2>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<a data-toggle="collapse" data-parent="#help" href="#sync_error">
					What does this sync error for my server mean?
				</a>
			</h3>
		</div>
		<div id="sync_error" class="panel-collapse collapse">
			<div class="panel-body">
				<dl class="spaced">
					<dt>Multiple hosts with same IP address</dt>
					<dd>At least one other host managed by SSL Cert Authority resolves to the same IP address as your server.  SSL Cert Authority will refuse to sync to either server until this is resolved.</dd>
					<dt>SSH connection failed</dt>
					<dd>SSL Cert Authority was unable to establish an SSH connection to your server.  This could indicate that the server is offline or otherwise unreachable, or that the SSH server is not running.</dd>
					<dt>SSH host key not supported</dt>
					<dd>Your Server is using a Host Key which is not supported by this application. Feel free to raise an issue <a href="https://github.com/mettke/ssl-cert-authority">here</a>.</dd>
					<dt>SSH host key verification failed</dt>
					<dd>SSL Cert Authority was able to open an SSH connection to your server, but the host key no longer matches the one that is on record for your server.  If this is expected (eg. your server has been migrated to a new host), you can reset the host key on the "Settings" page of your server. Press the "Clear" button for the host key fingerprint and then "Save changes".</dd>
					<?php if(!isset($security_config['host_key_collision_protection']) || $security_config['host_key_collision_protection'] == 1) { ?>
					<dt>Multiple hosts with same host key</dt>
					<dd>Your server has the same SSH host key as another server. This should be corrected by regenerating the SSH host keys on one or both of the affected servers.</dd>
					<?php } ?>
					<dt>SSH authentication failed</dt>
					<dd>Although SSL Cert Authority was able to connect to your server via SSH, it failed to log in.  See the guides for setting up <a data-toggle="collapse" data-parent="#help" href="#sync_setup">certificate syncing</a>.</dd>
					<?php if(isset($security_config['hostname_verification']) && $security_config['hostname_verification'] >= 3) { ?>
					<dt>Hostnames file missing</dt>
					<dd>The <code>/var/local/cert-sync/.hostnames</code> file does not exist on the server. SSL Cert Authority uses the contents of this file to verify that it is allowed to sync to your server.</dd>
					<dt>Hostname check failed</dt>
					<dd>The server name was not found in <code>/var/local/cert-sync/.hostnames</code> when SSL Cert Authority tried to sync to your server.</dd>
					<?php } ?>
					<dt>Cannot execute <em>x</em></dt>
					<dd>The server is missing a given program or the executable permission is not set on given program.</dd>
					<dt><em>x</em> failed to sync</dt>
					<dt>Failed to clean up <em>x</em> file(s)</dt>
					<dd>
						SSL Cert Authority could not write to at least one of the files in <code>/var/local/cert-sync</code>. This is typically caused by one of 3 possibilities:
						<ul>
							<li>Issues with file ownership - this directory and all files in it must be owned by the cert-sync user</li>
							<li>Read-only filesystem</li>
							<li>Disk full</li>
						</ul>
					</dd>
					<dt>Failed to restart service <em>x</em></dt>
					<dd>
						While restarting the service <em>x</em> either the restart, status or check script failed. 
						In this case either the scripts are not correct or there is an issue with the service. 
						Manually verify whether it is running and using the new certificate and check whether the scripts 
						are running fine using the cert-sync user.
					</dd>
				</dl>
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">
				<a data-toggle="collapse" data-parent="#help" href="#sync_setup">
					How do I set up my server to sync all certificates?
				</a>
			</h3>
		</div>
		<div id="sync_setup" class="panel-collapse collapse">
			<div class="panel-body">
				<h5>Stage 1</h5>
				<ol>
					<li>Create cert-sync account: <code>adduser --system --disabled-password --home /var/local/cert-sync --shell /bin/sh cert-sync</code>
					<li>Change the permissions of <code>/var/local/cert-sync</code> to 711: <code>chmod 0711 /var/local/cert-sync</code>
					<li>Add the following public key to the cert-sync user:
						<pre><?php out($this->get('cert-sync-pubkey'))?></pre>
					</li>
					<?php if(isset($security_config['hostname_verification']) && $security_config['hostname_verification'] >= 3) { ?>
					<li>Create <code>/var/local/cert-sync/.hostnames</code> text file (owned by cert-sync, permissions 0644) with the server's hostname in it</li>
					<?php } ?>
				</ol>
				<h5>Verify Stage 1 success</h5>
				<p>Once Stage 1 has been deployed to your server, trigger a resync from SSL Cert Authority. The server should synchronize successfully.</p>
			</div>
		</div>
	</div>
</div>
