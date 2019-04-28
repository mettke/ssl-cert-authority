<h1><span class="glyphicon glyphicon-certificate" title="Certificate"></span> <?php out($this->get('certificate')->name) ?></h1>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#usage" data-toggle="tab">Usage</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#migrate" data-toggle="tab">Migration</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Serial</dt>
			<dd><?php out($this->get('certificate')->serial)?></dd>
			<dt>Expiration Date</dt>
			<dd><?php out($this->get('certificate')->expiration)?></dd>
			<dt>Certificate (PEM Format)</dt>
			<dd><pre><?php out($this->get('certificate')->cert)?></pre></dd>
			<dt>Fullchain (PEM Format)</dt>
			<dd><pre><?php out($this->get('certificate')->fullchain)?></pre></dd>
		</dl>

		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<div class="col-sm-offset-0 col-sm-10">
					<button type="submit" name="delete_certificate" value="1" class="btn btn-primary">Delete certificate</button>
				</div>
			</div>
		</form>
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

	<div class="tab-pane fade" id="migrate">
		<h2 class="sr-only">Migrate</h2>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h2 class="panel-title">
					<a data-toggle="collapse" href="#information">
						Information
					</a>
				</h2>
			</div>
			<div id="information" class="panel-collapse collapse">
				<div class="panel-body">
					<p>
						The migration process will change the certificate of each profile associated with this certificate. 
						It allows an easy migration from an expiring certificate to its replacement. 
					</p>
				</div>
			</div>
		</div>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="certificate_id">Certificate</label>
				<select id="certificate_id" name="certificate_id" class="browser-default custom-select form-control" required>
					<option disabled selected></option>
					<?php foreach($this->get('all_certificates') as $certificate) { ?>
					<option value="<?php out($certificate->id)?>" label="<?php out($certificate->name)?>">
					<?php } ?>
				</select>
			</div>
			<button type="submit" name="migrate" value="1" class="btn btn-primary">Migrate</button>
		</form>
	</div>
</div>
