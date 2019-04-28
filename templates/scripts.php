<h1>Scripts</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">Script list</a></li>
	<li><a href="#add" data-toggle="tab">Add script</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
		<h2 class="sr-only">Script list</h2>
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
									<label for="name-search">Name (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="name-search" name="name" class="form-control" value="<?php out($this->get('filter')['name'])?>" autofocus>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<h4>Type</h4>
									<?php
									$options = array();
									$options['restart'] = 'Restart';
									$options['status'] = 'Status';
									$options['check'] = 'Check';
									foreach ($options as $value => $label) {
										$checked = in_array($value, $this->get('filter')['type']) ? ' checked' : '';
									?>
									<div class="checkbox"><label><input type="checkbox" name="type[]" value="<?php out($value)?>"<?php out($checked)?>> <?php out($label)?></label></div>
									<?php }?>
								</div>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Display results</button>
					</form>
				</div>
			</div>
		</div>
		<p><?php $total = count($this->get('scripts'));
		out(number_format($total) . ' script' . ($total == 1 ? '' : 's') . ' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
					<th>Type</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('scripts') as $script) {?>
					<tr>
						<td><a href="<?php outurl('/scripts/' . urlencode($script->name))?>" class="script"><?php out($script->name)?></a></td>
						<td><?php out($script->type)?></td>
					</tr>
				<?php }?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add script</h2>
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
						Sca uses scripts to check whether a certificate requires a replacement and to restart and check a service after deployment. There are three types of scripts:
						<ul>
							<li>
								<b>Check:</b><br>
								Checks whether a certificate deployment is necessary or whether is was successful. Executed before certificate deployment and after service verification via status script. Must return the certificate serial by querying the service using openssl. <br>
								Example:
								<pre>/usr/bin/openssl s_client -showcerts \
	  -servername &#60;server&#62; -connect &#60;server&#62;:443 \
	   &#60;/dev/null 2&#62;/dev/null \
   | /usr/bin/openssl x509 -noout -serial \
   | /usr/bin/cut -d'=' -f2</pre>
							</li>
							<li>
								<b>Restart:</b><br>
								Executed after certificate deployment. Supposed to restart a service which depends on that certifcate. Return code other then 0 will be treated as error.</li>
							<li>
								<b>Status:</b><br>
								Executed a few seconds after the restart script to check whether a service is running. Return code other then 0 will be treated as error.
							</li>
						</ul>
						Every script is executed on the server itself. It is thus possible to use `localhost` as hostname.
					</p>
				</div>
			</div>
		</div>

		<form method="post" action="<?php outurl($this->data->relative_request_url)?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE)?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="content">Content</label>
				<textarea id="content" name="content" cols="40" rows="8" class="form-control" placeholder="#!/usr/bin/env sh&#x0a;set -o nounset&#x0a;set -o errexit&#x0a;set -o pipefail&#x0a;echo 'Message'" required></textarea>
			</div>
			<div class="form-group">
				<label for="type">Type</label>
				<select id="type" name="type" class="browser-default custom-select form-control">
					<option value="restart" selected>Restart</option>
					<option value="status">Status</option>
					<option value="check">Check</option>
				</select>
			</div>
			<button type="submit" name="add_script" value="1" class="btn btn-primary">Add script</button>
		</form>
	</div>
</div>