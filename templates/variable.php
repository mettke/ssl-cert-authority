<h1><span class="glyphicon glyphicon-console" title="Varaible"></span> <?php out($this->get('variable')->name) ?></h1>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#edit" data-toggle="tab">Edit</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Value</dt>
			<dd><?php out($this->get('variable')->value) ?></dd>
			<dt>Description</dt>
			<dd><pre><?php out($this->get('variable')->description) ?></pre></dd>
			<dt>Service</dt>
			<dd><?php out($this->get('service')->name) ?></dd>
		</dl>
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
		<h2 class="sr-only">Edit</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" value="<?php out($this->get('variable')->name) ?>" required>
			</div>
			<div class="form-group">
				<label for="value">Value</label>
				<input type="text" id="value" name="value" class="form-control" value="<?php out($this->get('variable')->value) ?>" required>
			</div>
			<div class="form-group">
				<label for="description">Description</label>
				<textarea id="description" name="description" cols="40" rows="5" class="form-control"><?php out($this->get('variable')->description) ?></textarea>
			</div>
			<button type="submit" name="edit_variable" value="1" class="btn btn-primary">Edit variable</button>
			<button type="submit" name="delete_variable" value="1" class="btn btn-primary">Delete variable</button>
		</form>
	</div>
</div>
