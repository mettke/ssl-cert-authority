<h1>Services</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Service list</a></li>
	<li><a href="#add" data-toggle="tab">Add service</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
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
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add service</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="restart_script">Restart Script</label>
				<select id="restart_script" name="restart_script" class="browser-default custom-select form-control">
					<option selected></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "restart") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>">
					<?php } } ?>
				</select>
			</div>
			<div class="form-group">
				<label for="status_script">Status Script</label>
				<select id="status_script" name="status_script" class="browser-default custom-select form-control">
					<option selected></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "status") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>">
					<?php } } ?>
				</select>
			</div>
			<div class="form-group">
				<label for="check_script">Check Script</label>
				<select id="check_script" name="check_script" class="browser-default custom-select form-control">
					<option selected></option>
					<?php foreach($this->get('all_scripts') as $script) { if ($script->type == "check") { ?>
					<option value="<?php out($script->id)?>" label="<?php out($script->name)?>">
					<?php } } ?>
				</select>
			</div>
			<button type="submit" name="add_service" value="1" class="btn btn-primary">Add service</button>
		</form>
	</div>
</div>