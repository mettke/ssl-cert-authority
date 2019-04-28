<h1>Variables</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Variable list</a></li>
	<li><a href="#add" data-toggle="tab">Add variable</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
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
		<p><?php $total = count($this->get('variables'));
			out(number_format($total) . ' variable' . ($total == 1 ? '' : 's') . ' found') ?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('variables') as $variable) { ?>
					<tr>
						<td><a href="<?php outurl('/services/' . urlencode($this->get('service')->name) . '/variables/' . urlencode($variable->name)) ?>" class="variable"><?php out($variable->name) ?></a></td>
						<td><?php out($variable->value) ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add variablee</h2>
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
</div>