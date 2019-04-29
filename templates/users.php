<h1>Users</h1>
<ul class="nav nav-tabs">
	<li><a href="#list" data-toggle="tab">User list</a></li>
	<li><a href="#add" data-toggle="tab">Add user</a></li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
	<div class="tab-pane fade" id="list">
		<h2 class="sr-only">User list</h2>
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
									<label for="username-search">Username (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="username-search" name="uid" class="form-control" value="<?php out($this->get('filter')['uid'])?>" autofocus>
								</div>
								<div class="form-group">
									<label for="fullname-search">Full Name (<a href="https://mariadb.com/kb/en/mariadb/regular-expressions-overview/">regexp</a>)</label>
									<input type="text" id="fullname-search" name="name" class="form-control" value="<?php out($this->get('filter')['name'])?>">
								</div>
							</div>
						</div>
						<button type="submit" class="btn btn-primary">Display results</button>
					</form>
				</div>
			</div>
		</div>
		<p><?php $total = count($this->get('users')); out(number_format($total).' user'.($total == 1 ? '' : 's').' found')?></p>
		<table class="table table-hover table-condensed">
			<thead>
				<tr>
					<th>Username</th>
					<th>Full name</th>
					<th>Priviledge</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->get('users') as $user) { ?>
				<tr<?php if(!$user->active) out(' class="text-muted"', ESC_NONE) ?>>
					<td><a href="<?php outurl('/users/'.urlencode($user->uid))?>" class="user<?php if(!$user->active) out(' text-muted') ?>"><?php out($user->uid)?></a></td>
					<td><?php out($user->name)?></td>
					<td>
					<?php if($user->admin) { ?>Admin<?php } else { ?>User<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="tab-pane fade" id="add">
		<h2 class="sr-only">Add user</h2>
		<form method="post" action="<?php outurl($this->data->relative_request_url)?>">
			<?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
			<div class="form-group">
				<label for="uid">Username</label>
				<input type="text" id="uid" name="uid" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="name">Full Name</label>
				<input type="text" id="name" name="name" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="email">Mail Address</label>
				<input type="email" id="email" name="email" class="form-control" required>
			</div>		
			<input type="checkbox" name="admin" value="admin"> Administrator<br><br>
			<button type="submit" name="add_user" value="1" class="btn btn-primary">Add user</button>
		</form>
	</div>
</div>
