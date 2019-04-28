<h1>Profiles</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Profile list</a></li>
	<li><a href="#add" data-toggle="tab">Add profile</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
		<h2 class="sr-only">Profile list</h2>
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
			out(number_format($total) . ' profile' . ($total == 1 ? '' : 's') . ' found') ?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Certificate</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('profiles') as $profile) { ?>
					<tr>
						<td><a href="<?php outurl('/profiles/' . urlencode($profile->name)) ?>" class="profile"><?php out($profile->name) ?></a></td>
						<td><?php out($profile->certificate->name) ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add profile</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="certificate_id">Certificate</label>
				<select id="certificate_id" name="certificate_id" class="browser-default custom-select form-control" required>
					<option disabled selected></option>
					<?php foreach($this->get('all_certificates') as $certificate) { ?>
					<option value="<?php out($certificate->id)?>" label="<?php out($certificate->name)?>">
					<?php } ?>
				</select>
			</div>
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
			<button type="submit" name="add_profile" value="1" class="btn btn-primary">Add profile</button>
		</form>
	</div>
</div>