<h1><span class="glyphicon glyphicon-cloud" title="Service"></span> <?php out($this->get('service')->name) ?></h1>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#usage" data-toggle="tab">Usage</a></li>
	<li><a href="#var" data-toggle="tab">Variables</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#edit" data-toggle="tab">Edit</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Restart Script</dt>
			<dd><?php if(!is_null($this->get('service')->restart_script->name)) { ?><a href="<?php outurl('/scripts/' . urlencode($this->get('service')->restart_script->name))?>" class="script"><?php out($this->get('service')->restart_script->name)?></a><?php } ?>
			<dt>Status Script</dt>
			<dd><?php if(!is_null($this->get('service')->status_script->name)) { ?><a href="<?php outurl('/scripts/' . urlencode($this->get('service')->status_script->name))?>" class="script"><?php out($this->get('service')->status_script->name)?></a><?php } ?>
			<dt>Check Script</dt>
			<dd><?php if(!is_null($this->get('service')->check_script->name)) { ?><a href="<?php outurl('/scripts/' . urlencode($this->get('service')->check_script->name))?>" class="script"><?php out($this->get('service')->check_script->name)?></a><?php } ?>
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

	<div class="tab-pane fade" id="var">
		<h2 class="sr-only">Variable list</h2>
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
		<p><?php $total = count($this->get('all_variables'));
			out(number_format($total) . ' variable' . ($total == 1 ? '' : 's') . ' found') ?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('all_variables') as $variable) { ?>
					<tr>
						<td><a href="<?php outurl('/services/' . urlencode($this->get('service')->name) . '/variables/' . urlencode($variable->name)) ?>" class="variable"><?php out($variable->name) ?></a></td>
						<td><?php out($variable->value) ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="value">Value</label>
				<input type="text" id="value" name="value" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="description">Description</label>
				<textarea id="description" name="description" cols="40" rows="5" class="form-control"></textarea>
			</div>
			<button type="submit" name="add_variable" value="1" class="btn btn-primary">Add variable</button>
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
		<h2 class="sr-only">Edit service</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" value="<?php out($this->get('service')->name)?>" required>
			</div>
			<div class="form-group">
				<label for="restart_script">Restart Script</label>
				<select id="restart_script" name="restart_script" class="browser-default custom-select form-control">
					<option <?php if(!is_numeric($this->get('service')->restart_script_id)) out('selected', ESC_NONE); ?>></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "restart") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>" <?php if($this->get('service')->restart_script_id == $script->id) out('selected', ESC_NONE); ?>>
					<?php } } ?>
				</select>
			</div>
			<div class="form-group">
				<label for="status_script">Status Script</label>
				<select id="status_script" name="status_script" class="browser-default custom-select form-control">
					<option <?php if(!is_numeric($this->get('service')->status_script_id)) out('selected', ESC_NONE); ?>></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "status") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>" <?php if($this->get('service')->status_script_id == $script->id) out('selected', ESC_NONE); ?>>
					<?php } } ?>
				</select>
			</div>
			<div class="form-group">
				<label for="check_script">Check Script</label>
				<select id="check_script" name="check_script" class="browser-default custom-select form-control">
					<option <?php if(!is_numeric($this->get('service')->check_script_id)) out('selected', ESC_NONE); ?>></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "check") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>" <?php if($this->get('service')->check_script_id == $script->id) out('selected', ESC_NONE); ?>>
					<?php } } ?>
				</select>
			</div>
			<button type="submit" name="edit_service" value="1" class="btn btn-primary">Edit service</button>
			<button type="submit" name="delete_service" value="1" class="btn btn-primary">Delete service</button>
		</form>
	</div>
</div>