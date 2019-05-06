<h1>Servers</h1>
<form class="sync" method="post" action="<?php outurl($this->data->relative_request_url) ?>">
	<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
	<button type="submit" name="sync" value="1" class="btn btn-default btn-xs">Sync listed servers now</button>
</form>

<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Server list</a></li>
	<li><a href="#add" data-toggle="tab">Add server</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade in active" id="list">
		<h2 class="sr-only">Server list</h2>
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
							<div class="col-sm-6">
								<div class="form-group">
									<label for="hostname-search">Hostname (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="hostname-search" name="hostname" class="form-control" value="<?php out($this->get('filter')['hostname'])?>" autofocus>
								</div>
								<div class="form-group">
									<label for="ipaddress-search">IP address</label>
									<input type="text" id="ipaddress-search" name="ip_address" class="form-control" value="<?php out($this->get('filter')['ip_address'])?>">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<h4>Sync status</h4>
									<?php
									$options = array();
									$options['sync success'] = 'Sync success';
									$options['sync warning'] = 'Sync warning';
									$options['sync failure'] = 'Sync failure';
									$options['not synced yet'] = 'Not synced yet';
									$options['proposed'] = 'Proposed';
									foreach ($options as $value => $label) {
										$checked = in_array($value, $this->get('filter')['sync_status']) ? ' checked' : '';
									?>
									<div class="checkbox"><label><input type="checkbox" name="sync_status[]" value="<?php out($value)?>"<?php out($checked)?>> <?php out($label)?></label></div>
									<?php }?>
								</div>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Display results</button>
					</form>
				</div>
			</div>
		</div>
		<p><?php $total = count($this->get('servers')); out(number_format($total).' server'.($total == 1 ? '' : 's').' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Hostname</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($this->get('servers') as $server) {
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
					<td>
						<a href="<?php outurl('/servers/'.urlencode($server->hostname)) ?>" class="server"><?php out($server->hostname) ?></a>
					</td>
					<td class="<?php out($syncclass)?> nowrap">
						<?php out($sync_details)?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add server</h2>
		<div class="alert alert-info">
			See <a href="<?php outurl('/help#sync_setup')?>" class="alert-link">the sync setup instructions</a> for how to set up the server for cert synchronization.
		</div>
		<form method="post" action="<?php outurl($this->data->relative_request_url)?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="hostname">Server hostname</label>
				<input type="text" id="hostname" name="hostname" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="port">SSH port number</label>
				<input type="number" id="port" name="port" class="form-control" value="22" required>
			</div>
			<button type="submit" name="add_server" value="1" class="btn btn-primary">Add server to cert management</button>
		</form>
	</div>
</div>
