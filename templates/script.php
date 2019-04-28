<h1><span class="glyphicon glyphicon-file" title="Script"></span> <?php out($this->get('script')->name) ?></h1>
<dl>
	<dt>Script type</dt>
	<dd><?php out($this->get('script')->type) ?></dd>
</dl>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#usage" data-toggle="tab">Usage</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<li><a href="#edit" data-toggle="tab">Edit</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Content</dt>
			<dd><pre><?php out($this->get('script')->content) ?></pre></dd>
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
		<p><?php $total = count($this->get('services'));
		out(number_format($total) . ' service' . ($total == 1 ? '' : 's') . ' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Name</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($this->get('services') as $service) {?>
					<tr>
						<td><a href="<?php outurl('/services/' . urlencode($service->name))?>" class="service"><?php out($service->name)?></a></td>
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

	<div class="tab-pane fade" id="edit">
		<h2 class="sr-only">Edit</h2>
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
		<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="name">Name</label>
				<input type="text" id="name" name="name" class="form-control" value="<?php out($this->get('script')->name) ?>" required>
			</div>
			<div class="form-group">
				<label for="content">Content</label>
				<textarea id="content" name="content" cols="40" rows="20" class="form-control" required><?php out($this->get('script')->content) ?></textarea>
			</div>
			<div class="form-group">
				<label for="type">Type</label>
				<select id="type" name="type" class="browser-default custom-select form-control">
					<option value="restart" <?php if($this->get('script')->type == "restart") out('selected', ESC_NONE); ?>>Restart</option>
					<option value="status" <?php if($this->get('script')->type == "status") out('selected', ESC_NONE); ?>>Status</option>
					<option value="check" <?php if($this->get('script')->type == "check") out('selected', ESC_NONE); ?>>Check</option>
				</select>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-0 col-sm-10">
					<button type="submit" name="edit_script" value="1" class="btn btn-primary">Update script</button>
					<button type="submit" name="delete_script" value="1" class="btn btn-primary">Delete script</button>
				</div>
			</div>
		</form>
	</div>
</div>

