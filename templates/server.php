<h1><span class="glyphicon glyphicon-hdd" title="Server"></span> <?php out($this->get('server')->hostname) ?></h1>
<form method="post" action="<?php outurl($this->data->relative_request_url) ?>">
	<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
	<dl class="oneline">
		<?php if (isset($this->get('inventory_config')['url']) && $this->get('server')->uuid) { ?>
			<dt>Inventory UUID:</dt>
			<dd><a href="<?php out(printf($this->get('inventory_config')['url'], $this->get('server')->uuid), ESC_URL) ?>/"><?php out($this->get('server')->uuid) ?></a></dd>
		<?php } ?>
		<dt>Sync status:</dt>
		<dd id="server_sync_status"
		<?php if(count($this->get('sync_requests')) == 0) { ?>
		<?php if(is_null($this->get('last_sync'))) { ?>
		data-class="warning" data-message="Not synced yet"
		<?php } else { ?>
		data-class="<?php out($this->get('sync_class'))?>" data-message="<?php out(json_decode($this->get('last_sync')->details)->value) ?>"
		<?php } ?>
		<?php } ?>
		>
			<span></span>
			<div class="spinner"></div>
			<a href="<?php outurl('/help')?>" class="btn btn-info btn-xs hidden">Explain</a>
			<button name="sync" value="1" type="submit" class="btn btn-default btn-xs invisible">Sync now</button>
		</dd>
	</dl>
</form>
<?php if ($this->get('server')->ip_address && count($this->get('matching_servers_by_ip')) > 1) { ?>
	<div class="alert alert-danger">
		<p>The hostname <?php out($this->get('server')->hostname) ?> resolves to the same IP address as the following:</p>
		<ul>
			<?php foreach ($this->get('matching_servers_by_ip') as $matched_server) { ?>
				<?php if ($matched_server->hostname != $this->get('server')->hostname) { ?>
					<li><a href="<?php outurl('/servers/' . urlencode($matched_server->hostname)) ?>" class="server alert-link"><?php out($matched_server->hostname) ?></a></li>
				<?php } ?>
			<?php } ?>
		</ul>
	</div>
<?php } ?>
<?php if ($this->get('server')->rsa_key_fingerprint && count($this->get('matching_servers_by_host_key')) > 1) { ?>
	<div class="alert alert-danger">
		<p>The server has the same SSH host key as the following:</p>
		<ul>
			<?php foreach ($this->get('matching_servers_by_host_key') as $matched_server) { ?>
				<?php if ($matched_server->hostname != $this->get('server')->hostname) { ?>
					<li><a href="<?php outurl('/servers/' . urlencode($matched_server->hostname)) ?>" class="server alert-link"><?php out($matched_server->hostname) ?></a></li>
				<?php } ?>
			<?php } ?>
		</ul>
	</div>
<?php } ?>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#usage" data-toggle="tab">Usage</a></li>
	<li><a href="#notes" data-toggle="tab">Notes<?php if (count($this->get('server_notes')) > 0) out(' <span class="badge">' . count($this->get('server_notes')) . '</span>', ESC_NONE) ?></a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#edit" data-toggle="tab">Edit</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>UUID</dt>
			<dd><?php out($this->get('server')->uuid)?>
			<dt>port</dt>
			<dd><?php out($this->get('server')->port)?>
			<dt>ip_address</dt>
			<dd><?php out($this->get('server')->ip_address)?>
			<dt>rsa_key_fingerprint</dt>
			<dd><?php out($this->get('server')->rsa_key_fingerprint)?>
		</dl>
	</div>

	<div class="tab-pane fade" id="usage">
		<h2 class="sr-only">Usage</h2>
		<div class="panel-group">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						Filter options
					</h3>
				</div>
				<div class="panel-body">
					<form>
						<div class="row">
							<div class="col-sm-12">
								<div class="form-group">
									<label for="name-search">Name (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="name-search" name="name" class="form-control" value="<?php out($this->get('filter')['name'])?>" autofocus>
								</div>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Display results</button>
					</form>
				</div>
			</div>
		</div>
		<p><?php $total = count($this->get('profiles'));
		out(number_format($total) . ' profile' . ($total == 1 ? '' : 's') . ' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('profiles') as $profile) {?>
					<tr>
						<td><a href="<?php outurl('/profiles/' . urlencode($profile->name))?>" class="profile"><?php out($profile->name)?></a></td>
					</tr>
				<?php }?>
			</tbody>
		</table>
	</div>
	
	<div class="tab-pane fade" id="log">
		<h2 class="sr-only">Log</h2>
		<table class="table">
			<col>
			</col>
			<col>
			</col>
			<col>
			</col>
			<col class="date">
			</col>
			<thead>
				<tr>
					<th>Entity</th>
					<th>User</th>
					<th>Activity</th>
					<th>Date (<abbr title="Coordinated Universal Time">UTC</abbr>)</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($this->get('log') as $event) {
					show_event($event);
				}
				?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="notes">
		<h2 class="sr-only">Notes</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<?php foreach ($this->get('server_notes') as $note) { ?>
				<div class="panel panel-default">
					<div class="panel-body pre-formatted"><?php out($this->get('output_formatter')->comment_format($note->note), ESC_NONE) ?></div>
					<div class="panel-footer">
						Added <?php out($note->date) ?> by <?php if (is_null($note->user->uid)) { ?>removed<?php } else { ?><a href="<?php outurl('/users/' . urlencode($note->user->uid)) ?>" class="user"><?php out($note->user->uid) ?></a><?php } ?>
						<button name="delete_note" value="<?php out($note->id) ?>" class="pull-right btn btn-default btn-xs"><span class="glyphicon glyphicon-trash"></span> Delete</button>
					</div>
				</div>
			<?php } ?>
		</form>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="note">Note</label>
				<textarea class="form-control" rows="4" id="note" name="note" required></textarea>
			</div>
			<div class="form-group">
				<button type="submit" name="add_note" value="1" class="btn btn-primary btn-lg btn-block">Add note</button>
			</div>
		</form>
	</div>
	
	<div class="tab-pane fade" id="edit">
		<h2 class="sr-only">Edit server</h2>
		<form id="server_settings" method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="hostname" class="col-sm-2 control-label">Hostname</label>
				<div class="col-sm-10">
					<input type="text" id="hostname" name="hostname" value="<?php out($this->get('server')->hostname) ?>" required class="form-control">
				</div>
			</div>
			<div class="form-group">
				<label for="port" class="col-sm-2 control-label">SSH port number</label>
				<div class="col-sm-2">
					<input type="number" id="port" name="port" value="<?php out($this->get('server')->port) ?>" required class="form-control">
				</div>
			</div>
			<div class="form-group">
				<label for="rsa_key_fingerprint" class="col-sm-2 control-label">Host key fingerprint</label>
				<div class="col-sm-8">
					<input type="text" id="rsa_key_fingerprint" name="rsa_key_fingerprint" value="<?php out($this->get('server')->rsa_key_fingerprint) ?>" readonly class="form-control">
				</div>
				<div class="col-sm-2">
					<button type="button" class="btn btn-default" data-clear="rsa_key_fingerprint">Clear</button>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" name="edit_server" value="1" class="btn btn-primary">Edit server</button>
					<button type="submit" name="delete_server" value="1" class="btn btn-primary">Delete server</button>
				</div>
			</div>
		</form>
	</div>
</div>