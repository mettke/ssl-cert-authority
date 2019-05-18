<h1>Certificates</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Certificate list</a></li>
	<li><a href="#add" data-toggle="tab">Add Signing Request</a></li>
	<li><a href="#upload" data-toggle="tab">Upload certificate</a></li>
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
		<h2 class="sr-only">Add Signing Request</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url)?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="subject">Subject</label>
				<input type="text" id="subject" name="subject" class="form-control" placeholder="/CN=&#60;domain&#62;" required>
			</div>
			<div class="form-group">
				<label for="key_type">Key Type</label>
				<select id="key_type" name="key_type" class="browser-default custom-select form-control" required>
					<option value="rsa8192" label="RSA 8192">
					<option value="rsa4096" label="RSA 4096" selected>
					<option value="rsa2048" label="RSA 2048">
					<option value="ecdsa521" label="ECDSA Secp 521">
					<option value="ecdsa384" label="ECDSA Secp 384">
					<option value="ecdsa256" label="ECDSA Secp 256">
					<option value="ed25519" label="EdDSA Ed25519">
				</select>
			</div>
			<button type="submit" name="add_signing_request" value="1" class="btn btn-primary">Add certificate</button>
		</form>
	</div>

	<div class="tab-pane fade" id="upload">
		<h2 class="sr-only">Upload certificate</h2>
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
			<button type="submit" name="upload_certificate" value="1" class="btn btn-primary">Add certificate</button>
		</form>
	</div>
</div>
