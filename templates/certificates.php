<h1>Certificates</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Certificate list</a></li>
	<li><a href="#add" data-toggle="tab">Add certificate</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
		<h2 class="sr-only">Certificate list</h2>
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
								<div class="form-group">
									<label for="serial-search">Serial (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="serial-search" name="serial" class="form-control" value="<?php out($this->get('filter')['serial'])?>">
								</div>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Display results</button>
					</form>
				</div>
			</div>
		</div>
		<p><?php $total = count($this->get('certificates')); out(number_format($total).' certificate'.($total == 1 ? '' : 's').' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Serial</th>
					<th>Expiration</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->get('certificates') as $certificate) { ?>
				<tr>
					<td><a href="<?php outurl('/certificates/'.urlencode($certificate->name))?>" class="certificate"><?php out($certificate->name)?></a></td>
					<td><?php out($certificate->serial)?></td>
					<td><?php out($certificate->expiration)?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add certificate</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url)?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="private">Private Key (PEM Format)</label>
				<textarea id="private" name="private" cols="40" rows="5" class="form-control" required></textarea>
			</div>
			<div class="form-group">
				<label for="password">Password (Encryption requires JavaScript)</label>
				<textarea readonly id="password" name="password" cols="40" rows="5" class="form-control"></textarea>
			</div>
			<div class="form-group">
				<label for="cert">Cert (PEM Format)</label>
				<textarea id="cert" name="cert" cols="40" rows="5" class="form-control" required></textarea>
			</div>
			<div class="form-group">
				<label for="fullchain">Fullchain (PEM Format)</label>
				<textarea id="fullchain" name="fullchain" cols="40" rows="5" class="form-control" required></textarea>
			</div>
			<button type="submit" name="add_certificate" value="1" class="btn btn-primary">Add certificate</button>
		</form>
	</div>
</div>
