<h1><span class="glyphicon glyphicon-book" title="Profile"></span> <?php out($this->get('profile')->name) ?></h1>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#servers" data-toggle="tab">Servers</a></li>
	<li><a href="#services" data-toggle="tab">Services</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#edit" data-toggle="tab">Edit</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Certificate</dt>
			<dd><a href="<?php outurl('/certificates/' . urlencode($this->get('profile')->certificate->name))?>" class="certificate"><?php out($this->get('profile')->certificate->name)?></a>
		</dl>
	</div>

	<div class="tab-pane fade" id="servers">
		<h2 class="sr-only">Server list</h2>
		<form class="sync" method="post" action="<?php outurl($this->data->relative_request_url) ?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<button type="submit" name="sync" value="1" class="btn btn-default btn-xs">Sync listed servers now</button>
		</form>
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
		<p><?php $total = count($this->get('servers'));
			out(number_format($total) . ' server' . ($total == 1 ? '' : 's') . ' found') ?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Sync Status</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('servers') as $server) { 
					switch($server->sync_status) {
						case 'not synced yet': $syncclass = 'warning'; break;
						case 'sync failure':   $syncclass = 'danger';  break;
						case 'sync success':   $syncclass = 'success'; break;
						case 'sync warning':   $syncclass = 'warning'; break;
						case 'proposed':   $syncclass = 'warning'; break;
					}
					if($last_sync = $server->get_last_sync_event()) {
						$sync_details = json_decode($last_sync->details)->value;
					} else {
						$sync_details = ucfirst($server->sync_status);
					}
				?>
					<tr>
						<td><a href="<?php outurl('/servers/' . urlencode($server->hostname)) ?>" class="server"><?php out($server->hostname) ?></a></td>
						<td class="<?php out($syncclass)?> nowrap">
							<?php out($sync_details)?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="profile_server">Servers</label>
				<input type="text" id="profile_servers" name="servers" class="form-control hidden">
				<input type="text" id="profile_server" name="server" class="form-control" placeholder="Type server name and press 'Enter' key" list="serverlist">
				<datalist id="serverlist">
					<?php foreach($this->get('all_servers') as $server) { ?>
					<option value="<?php out($server->hostname)?>" label="<?php out($server->hostname)?>">
					<?php } ?>
				</datalist>
			</div>
			<button type="submit" name="add_servers" value="1" class="btn btn-primary">Add Servers</button>
		</form>
	</div>

	<div class="tab-pane fade" id="services">
		<h2 class="sr-only">Service list</h2>
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
		<p><?php $total = count($this->get('services'));
			out(number_format($total) . ' service' . ($total == 1 ? '' : 's') . ' found') ?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('services') as $service) { ?>
					<tr>
						<td><a href="<?php outurl('/services/' . urlencode($service->name)) ?>" class="service"><?php out($service->name) ?></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="profile_service">Services</label>
				<input type="text" id="profile_services" name="services" class="form-control hidden">
				<input type="text" id="profile_service" name="service" class="form-control" placeholder="Type service name and press 'Enter' key" list="servicelist">
				<datalist id="servicelist">
					<?php foreach($this->get('all_services') as $service) { ?>
					<option value="<?php out($service->name)?>" label="<?php out($service->name)?>">
					<?php } ?>
				</datalist>
			</div>
			<button type="submit" name="add_services" value="1" class="btn btn-primary">Add Services</button>
		</form>
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

	<div class="tab-pane fade" id="edit">
		<h2 class="sr-only">Edit profile</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" value="<?php out($this->get('profile')->name)?>" required>
			</div>
			<div class="form-group">
				<label for="certificate_id">Certificate</label>
				<select id="certificate_id" name="certificate_id" class="browser-default custom-select form-control" required>
					<option disabled <?php if(!is_numeric($this->get('profile')->certificate_id)) out('selected', ESC_NONE); ?>></option>
					<?php foreach($this->get('all_certificates') as $certificate) { ?>
					<option value="<?php out($certificate->id)?>" label="<?php out($certificate->name)?>" <?php if($this->get('profile')->certificate_id == $certificate->id) out('selected', ESC_NONE); ?>>
					<?php } ?>
				</select>
			</div>
			<button type="submit" name="edit_profile" value="1" class="btn btn-primary">Edit profile</button>
			<button type="submit" name="delete_profile" value="1" class="btn btn-primary">Delete profile</button>
		</form>
	</div>
</div>