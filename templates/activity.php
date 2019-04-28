<h1>Activity</h1>
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
					<div class="col-sm-5">
						<div class="form-group">
							<label for="id-search">Object ID</label>
							<input disabled type="text" id="id-search" name="object_id" class="form-control" value="<?php out($this->get('filter')['object_id'])?>" autofocus>
						</div>
					</div>
					<div class="col-sm-5">
						<div class="form-group">
							<label for="serial-search">Type</label>
							<input disabled type="text" id="serial-search" name="serial" class="form-control" value="<?php out($this->get('filter')['type'])?>">
						</div>
					</div>
					<div class="col-sm-2">
						<div class="form-group">
							<label for="limit">Limit</label>
							<input type="number" id="limit" name="limit" class="form-control" value="<?php out($this->get('filter')['limit'])?>">
						</div>
					</div>
				</div>
				<button type="submit" class="btn btn-primary">Display results</button>
				<button type="submit" name="clear" value="1" class="btn btn-primary">Clear</button>
			</form>
		</div>
	</div>
</div>
<table class="table">
	<col></col>
	<col></col>
	<col></col>
	<col class="date"></col>
	<thead>
		<tr>
			<th></th>
			<th>Entity</th>
			<th>User</th>
			<th>Activity</th>
			<th>Date (<abbr title="Coordinated Universal Time">UTC</abbr>)</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach($this->get('events') as $event) {
			show_event($event, TRUE);
		}
		?>
	</tbody>
</table>
