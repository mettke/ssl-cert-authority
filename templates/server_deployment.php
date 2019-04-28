<h1>Certificate Deployment</h1>
<div>
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
                    A synchronization updates everything under <code>/var/local/cert-sync</code>. 
                    Removing a certificate or profile will remove it from the server possibly 
                    rendering services unusable. <em>If you don't know what you're doing, <a href="" class="navigate-back">cancel</a>
                    now and contact someone who does!</em>
                    <ul>
                        <li>
                            <b>Certificates:</b><br>
                            Certificates are stored under <code>/var/local/cert-sync/cert/{fullchain,private,cert}/name</code>. 
                            When updating certificates, the old ones are removed and new ones are added. 
                            Certificate changes will trigger a restart of services.                         
                        </li>
                        <li>
                            <b>Profiles:</b><br>
                            Profiles are stored under <code>/var/local/cert-sync/profile/name/{cert,fullchain,private}</code>.
                            They are symbolic links to the certificates above and always get renewed.
                            Profile changes will trigger a restart of services.
                        <li>
                            <b>Variables:</b><br>
                            Variables are stored as scripts under <code>/var/local/cert-sync/variable/name</code>.
                            Variable changes will not trigger a restart of services.
                        </li>
                        <li>
                            <b>Scripts:</b><br>
                            Scripts are stored under <code>/var/local/cert-sync/script/name</code>.
                            Script changes will not trigger a restart of services.
                        </li>
                    </ul>
                    SCA will only display generic error messages as server synchronisation status.
                    To quickly react to difficultes it is advices to follow the error log file using
                    a command like: <br> <code>tail -f /var/log/cert/sync.log</code><br><br>
                    
                    Note that only one server and service will be deployed/restarted at a time. If a
                    problem is detected the synchronisation will stop for all servers, thus allowing
                    a quick repair without having several servers fail at once. The error detecting,
                    however, requires scripts to be well written. <br>
                    <em>Always proceed with caution.</em>
                </p>
            </div>
        </div>
    </div>
    <p>
        Are you sure you want to synchronize the following servers? Please read the information above to prepare for the possible difficulties.<br>
        <?php $total = count($this->get('servers'));
        out(number_format($total) . ' server' . ($total == 1 ? '' : 's'))?>
    </p>
    <table class="table table-hover table-condensed">
        <thead>
            <tr>
                <th>Name</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->get('servers') as $server) {?>
                <tr>
                    <td><a href="<?php outurl('/server/' . urlencode($server->hostname))?>" class="server"><?php out($server->hostname)?></a></td>
                </tr>
            <?php }?>
        </tbody>
    </table>
    <form method="post" action="<?php outurl($this->data->relative_request_url) ?>" class="form-horizontal">
        <?php out($this->get('active_user')->get_csrf_field(), ESC_NONE) ?>
        <div class="form-group">
            <div class="col-sm-offset-0 col-sm-10">
                <button type="submit" name="sync_confirm" value="1" class="btn btn-primary">Confirm</button>
                <a href="" class="navigate-back">Cancel</a>
            </div>
        </div>
    </form>
</div>
