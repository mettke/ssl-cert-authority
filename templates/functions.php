<?php
function show_event($event, $show_filter=false) {
	$json = json_decode($event->details);
	$details = hesc($event->details);
	switch($json->action) {
		case 'User add':
			$details = 'Added user';
			break;
		case 'User del':
			$details = 'Deleted user with name '.hesc($json->name);
			break;

		case 'Script add':
			$details = 'Added script';
			break;
		case 'Script updated':
			$details = 'Script content was updated';
			break;
		case 'Script del':
			$details = 'Deleted script with name '.hesc($json->name);
			break;

		case 'Service add':
			$details = 'Added service';
			break;
		case 'Variable add':
			$details = 'Added variable to service '.hesc($json->service);
			break;
		case 'Variable updated':
			$details = 'Variable description was updated';
			break;
		case 'Variable del':
			$details = 'Deleted variable with name '.hesc($json->name).' from service '.hesc($json->service);
			break;
		case 'Service del':
			$details = 'Deleted service with name '.hesc($json->name);
			break;

		case 'Certificate add':
			$details = 'Added certificate';
			break;
		case 'Certificate del':
			$details = 'Deleted certificate with name '.hesc($json->name);
			break;

		case 'Server add':
			$details = 'Added server';
			break;
		case 'Note add':
			$details = 'Added note to server';
			break;
		case 'Note modified':
			$details = 'Added note to server';
			break;
		case 'Note del':
			$details = 'Deleted note from server';
			break;
		case 'Server del':
			$details = 'Deleted server with name '.hesc($json->name);
			break;


		case 'Profile add':
			$details = 'Added profile';
			break;
		case 'Server rel add':
			$details = 'Added server \''.hesc($json->name).'\' to profile';
			break;
		case 'Server rel del':
			$details = 'Deleted server \''.hesc($json->name).'\' from profile';
			break;
		case 'Service rel add':
			$details = 'Added service \''.hesc($json->name).'\' to profile';
			break;
		case 'Service rel del':
			$details = 'Deleted service \''.hesc($json->name).'\' from profile';
			break;
		case 'Profile del':
			$details = 'Deleted profile with name '.hesc($json->name);
			break;

		case 'Setting update':
			$details = hesc($json->field).' changed from <q>'.hesc($json->oldvalue).'</q> to <q>'.hesc($json->value).'</q>';
			break;
		case 'Sync status change':
			$details = 'Sync status: '.hesc($json->value);
			break;
	}
	?>
	<tr>
		<?php if($show_filter) { ?>
		<td><a href="<?php outurl('?object_id='.urlencode($event->object_id).'&type='.urlencode($event->type))?>" class="filter"></a></td>
		<?php } ?>
		<td>
			<?php if($event->type == 'ServiceVariable') { ?>
				<?php if(is_null($event->name)) { ?>
				<span>removed</span>
				<?php } else { ?>
				<a href="<?php outurl('/services/'.urlencode(strtolower($event->object->service->name)).'/variables/'.urlencode($event->name))?>" class="variable"><?php out($event->name) ?></a>
				<?php } ?>
			<?php } else { ?>
				<?php if(is_null($event->name)) { ?>
				<span>removed</span>
				<?php } else { ?>
				<a href="<?php outurl('/'.urlencode(strtolower($event->type)).'s/'.urlencode($event->name))?>" class="<?php out(strtolower($event->type)) ?>"><?php out($event->name) ?></a>
				<?php } ?>
			<?php } ?>
		</td>
		<?php if(is_null($event->actor->uid)) { ?>
		<td>removed</td>
		<?php } else { ?>
		<td><a href="<?php outurl('/users/'.urlencode($event->actor->uid))?>" class="user"><?php out($event->actor->uid) ?></a></td>
		<?php } ?>
		<td><?php out($details, ESC_NONE) ?></td>
		<td class="nowrap"><?php out($event->date) ?></td>
	</tr>
	<?php
}
function show_event_participant($participant) {
	list($type, $name) = explode(':', $participant, 2);
	if($type == 'user') {
		return '<a href="'.rrurl('/users/'.urlencode($name)).'" class="user">'.hesc($name).'</a>';
	} else {
		return hesc($participant);
	}
}
function keygen_help($box_position) {
	?>
	<ul class="nav nav-tabs">
		<li><a href="#windows_instructions" data-toggle="tab">Windows</a></li>
		<li><a href="#mac_instructions" data-toggle="tab">Mac</a></li>
		<li><a href="#linux_instructions" data-toggle="tab">Linux</a></li>
	</ul>
	<div class="tab-content clearfix">
		<div class="tab-pane fade" id="windows_instructions">
			<aside class="pull-right"><img src="/putty-key-generator.png" class="img-rounded"></aside>
			<p>On Windows you will typically use the <a href="http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html">PuTTYgen</a> application to generate your key pair.</p>
			<ol>
				<li>Download and run the latest Windows installer from the <a href="http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html">PuTTY download page</a>.
				<li>Start PuTTYgen.
				<li>Select the type of key to generate. RSA, ECDSA or ED25519 are good choices.
				<li>For RSA, enter "4096" as the number of bits in the generated key. For ECDSA, use either the nistp384 or nistp521 curve.
				<li>Click the Generate button.
				<li>Provide a comment for the key: This comment allows to identify your key. Best thing is to use your username or email address.
				<li><strong>Provide a key passphrase.</strong>
				<li>Save the private key to your local machine.
				<li>Select and copy the contents of the "Public key for pasting into OpenSSH authorized_keys file" section at the top of the window (scrollable, make sure to select all).
				<?php if(!is_null($box_position)) { ?>
				<li>Paste the public key that you just copied into the box <?php out($box_position)?> and click the "Add public key" button.
				<?php } ?>
			</ol>
			<div class="alert alert-info">
				<strong>Note:</strong> if you are not using PuTTY to connect, you may need to export your private key into OpenSSH format to use it. You can do this from the Conversions menu.
			</div>
			<div class="alert alert-info">
				<strong>Note:</strong> if you are using Cygwin or MSYS bash, the instructions for Linux can be used instead.
			</div>
		</div>
		<div class="tab-pane fade" id="mac_instructions">
			<p>On Mac you can generate a key pair with the ssh-keygen command.</p>
			<ol>
				<li>Start the "Terminal" program.
				<li>Run one of the following commands. Make sure to replace '<var>comment</var>' with a text that identifies you. Best thing is to use your username or email address.
					<ul>
						<li>rsa: <code>ssh-keygen -t rsa -b 4096 -C '<var>comment</var>'</code>
						<li>ecdsa: <code>ssh-keygen -t ecdsa -b 256 -C '<var>comment</var>'</code>
						<li>ed25519: <code>ssh-keygen -t ed25519 -C '<var>comment</var>'</code>
					</ul>
				<li><strong>Make sure that you give the key a passphrase when prompted.</strong>
				<li>A new text file will have been created in a <code>.ssh</code> directory. Copy the contents of one of the following files into your clipboard (depends on the algorithm you used).
					<ul>
						<li>rsa: <code>id_rsa.pub</code>
						<li>ecdsa: <code>id_ecdsa.pub</code>
						<li>ed25519: <code>id_ed25519.pub</code>
					</ul>
				<?php if(!is_null($box_position)) { ?>
				<li>Paste the public key that you just copied into the box <?php out($box_position)?> and click the "Add public key" button.
				<?php } ?>
			</ol>
		</div>
		<div class="tab-pane fade" id="linux_instructions">
			<p>On Linux you can generate a key pair with the ssh-keygen command.</p>
			<ol>
				<li>Open a terminal on your machine
				<li>Run one of the following commands. Make sure to replace '<var>comment</var>' with a text that identifies you. Best thing is to use your username or email address.
					<ul>
						<li>rsa: <code>ssh-keygen -t rsa -b 4096 -C '<var>comment</var>'</code>
						<li>ecdsa: <code>ssh-keygen -t ecdsa -b 256 -C '<var>comment</var>'</code>
						<li>ed25519: <code>ssh-keygen -t ed25519 -C '<var>comment</var>'</code>
					</ul>
					<div class="alert alert-info">
						Note: if this command fails with a message of "ssh-keygen: command not found", you need to install the openssh-client package: <code>sudo apt-get install openssh-client</code> on Debian-based systems.
					</div>
				<li><strong>Make sure that you give the key a passphrase when prompted.</strong>
				<li>A new text file will have been created in a <code>.ssh</code> directory. Copy the contents of one of the following files into your clipboard (depends on the algorithm you used).
					<ul>
						<li>rsa: <code>id_rsa.pub</code>
						<li>ecdsa: <code>id_ecdsa.pub</code>
						<li>ed25519: <code>id_ed25519.pub</code>
					</ul>
				<?php if(!is_null($box_position)) { ?>
				<li>Paste the public key that you just copied into the box <?php out($box_position)?> and click the "Add public key" button.
				<?php } ?>
			</ol>
		</div>
	</div>
	<?php
}
