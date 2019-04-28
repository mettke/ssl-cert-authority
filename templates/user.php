<h1><span class="glyphicon glyphicon-user" title="User"></span> <?php out($this->get('user')->name) ?> <small>(<?php out($this->get('user')->uid) ?>)</small><?php if (!$this->get('user')->active) out(' <span class="label label-default">Inactive</span>', ESC_NONE) ?></h1>
<dl>
	<dt>Account type</dt>
	<dd><?php out($this->get('user')->auth_realm) ?></dd>
</dl>
<ul class="nav nav-tabs">
	<li><a href="#view" data-toggle="tab">View</a></li>
	<li><a href="#log" data-toggle="tab">Log</a></li>
	<?php if (($this->get('user')->auth_realm == 'local' && $this->get('user')->uid != 'cert-sync') || $this->get('user')->auth_realm == 'LDAP') { ?>
		<li><a href="#settings" data-toggle="tab">Settings</a></li>
	<?php } ?>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="view">
		<h2 class="sr-only">View</h2>
		<dl>
			<dt>Username</dt>
			<dd><?php out($this->get('user')->uid) ?></dd>
			<dt>Full Name</dt>
			<dd><?php out($this->get('user')->name) ?></dd>
			<dt>Mail Address</dt>
			<dd><?php out($this->get('user')->email) ?></dd>
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

	<?php if (($this->get('user')->auth_realm == 'local' && $this->get('user')->uid != 'cert-sync') || $this->get('user')->auth_realm == 'LDAP') { ?>
		<div class="tab-pane fade" id="settings">
			<h2 class="sr-only">Settings</h2>
			<?php if ($this->get('user')->auth_realm == 'local') { ?>
				<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
					<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
					<div class="form-group">
						<label for="uid">Username</label>
						<input type="text" id="uid" name="uid" value="<?php out($this->get('user')->uid) ?>" class="form-control" required>
					</div>
					<div class="form-group">
						<label for="name">Full Name</label>
						<input type="text" id="name" name="name" value="<?php out($this->get('user')->name) ?>" class="form-control" required>
					</div>
					<div class="form-group">
						<label for="email">Mail Address</label>
						<input type="email" id="email" name="email" value="<?php out($this->get('user')->email) ?>" class="form-control" required>
					</div>
					<button type="submit" name="edit_user" value="1" class="btn btn-primary">Edit user</button>
					<button type="submit" name="delete_user" value="1" class="btn btn-primary">Delete user</button>
				</form>
			<?php } elseif ($this->get('user')->auth_realm == 'LDAP') { ?>
				<form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
					<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
					<div class="form-group">
						<label class="col-sm-2 control-label">User status</label>
						<div class="col-sm-10">
							<div class="radio">
								<label>
									<input type="radio" name="force_disable" value="0" <?php if (!$this->get('user')->force_disable) out(' checked') ?>>
									Use status from LDAP
								</label>
							</div>
							<div class="radio">
								<label class="text-danger">
									<input type="radio" name="force_disable" value="1" <?php if ($this->get('user')->force_disable) out(' checked') ?>>
									Disable account (override LDAP)
								</label>
							</div>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" name="edit_user" value="1" class="btn btn-primary">Change settings</button>
						</div>
					</div>
				</form>
			<?php } ?>
		</div>
	<?php } ?>
</div>
