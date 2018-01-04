<?php
class BillingCycles {
	public $settings = array(
		'name' => 'Billing Cycles',
		
		'admin_menu_category' => 'Ordering',
		'admin_menu_name' => 'Billing Cycles',
		'admin_menu_icon' => '<i class="icon-clock"></i>',
		'description' => 'Control how often your clients should pay you for a product or service.',
	);
	function list_billing_cycles() {
		global $billic, $db;
		$billingcycles = array();
		$billingcycles_raw = $db->q('SELECT * FROM `billingcycles`');
		foreach($billingcycles_raw as $billingcycle) {
			$billingcycles[$billingcycle['name']] = $billingcycle;
		}
		unset($billingcycles_raw);
		return $billingcycles;
	}
	function admin_area() {
		global $billic, $db;
		
		if (isset($_GET['Name'])) {
                    if (isset($_GET['ImportHash'])) {
                        $billingcycle = $db->q('SELECT * FROM `billingcycles` WHERE `name` = ? AND `import_hash` = ?', urldecode($_GET['Name']), urldecode($_GET['ImportHash']));
                    } else {
			$billingcycle = $db->q('SELECT * FROM `billingcycles` WHERE `name` = ?', urldecode($_GET['Name']));
                    }
			$billingcycle = $billingcycle[0];
			if (empty($billingcycle)) {
				err('Billing Cycle does not exist');
			}

			$billic->set_title('Admin/Billing Cycle '.safe($billingcycle['name']));
			echo '<h1><i class="icon-clock"></i> Billing Cycle '.safe($billingcycle['name']).'</h1>';

			if (isset($_POST['update'])) {
				if (empty($_POST['name'])) {
				    $billic->error('Name can not be empty', 'name');
				} else
				if (strlen($_POST['name'])>127) {
				    $billic->error('Name must be less than 128 characters', 'name');
				} else {
				    $name_check = $db->q('SELECT COUNT(*) FROM `billingcycles` WHERE `name` = ? AND `import_hash` = ?', $_POST['name'], urldecode($_GET['import_hash']));
				    if ($name_check[0]['COUNT(*)']>1) {
					$billic->error('Name is already in use by a different billing cycle', 'name');
				    }
				}
				
				if (empty($billic->errors)) {
                                    $db->q('UPDATE `billingcycles` SET `name` = ?, `displayname1` = ?, `displayname2` = ?, `multiplier` = ?, `seconds` = ?, `discount` = ? WHERE `name` = ? AND `import_hash` = ?', $_POST['name'], $_POST['displayname1'], $_POST['displayname2'], $_POST['multiplier'], $_POST['seconds'], $_POST['discount'], urldecode($_GET['Name']), urldecode($_GET['import_hash']));
                                    $billic->redirect('/Admin/BillingCycles/Name/'.urlencode($_POST['name']).'/');
				}
			}
			
			$billic->show_errors();

			echo '<form method="POST"><table class="table table-striped"><tr><th colspan="2">Billing Cycle Settings</th></td></tr>';
			echo '<tr><td width="125">Name</td><td><input type="text" class="form-control" name="name" value="'.$billingcycle['name'].'"></td></tr>';
			echo '<tr><td width="125">Display Name 1</td><td><input type="text" class="form-control" name="displayname1" value="'.(isset($_POST['displayname1'])?safe($_POST['displayname1']):safe($billingcycle['displayname1'])).'"></td></tr>';
			echo '<tr><td width="125">Display Name 2</td><td><input type="text" class="form-control" name="displayname2" value="'.(isset($_POST['displayname2'])?safe($_POST['displayname2']):safe($billingcycle['displayname2'])).'"></td></tr>';
			echo '<tr><td width="125">Price Multiplier</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="multiplier" value="'.(isset($_POST['multiplier'])?safe($_POST['multiplier']):safe($billingcycle['multiplier'])).'"><span class="input-group-addon" id="basic-addon2">x</div></div></td></tr>';
			echo '<tr><td width="125">Billing Time</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="seconds" value="'.(isset($_POST['seconds'])?safe($_POST['seconds']):safe($billingcycle['seconds'])).'"><span class="input-group-addon" id="basic-addon2">seconds</div></div></td></tr>';
			echo '<tr><td width="125">Discount</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="discount" value="'.(isset($_POST['discount'])?safe($_POST['discount']):safe($billingcycle['discount'])).'"><span class="input-group-addon" id="basic-addon2">%</div></div></td></tr>';
			echo '</td></tr><tr><td colspan="4" align="center"><input type="submit" class="btn btn-success" name="update" value="Update &raquo;"></td></tr></table></form>';
			return;
		}
		
		if (isset($_GET['New'])) {
			$title = 'New Billing Cycle';
			$billic->set_title($title);
			echo '<h1>'.$title.'</h1>';

            $billic->module('FormBuilder');
			$form = array(
				'name' => array(
					'label' => 'Name',
					'type' => 'text',
					'required' => true, 
					'default' => '',
				),
			);
			if (isset($_POST['Continue'])) {
                $billic->modules['FormBuilder']->check_everything(array(
                    'form' => $form,
                ));
				if (strlen($_POST['name'])>127) {
				    $billic->error('Name must be less than 128 characters', 'name');
				}
				if (empty($billic->errors)) {
					$db->insert('billingcycles', array(
						'name' => $_POST['name'],
						'multiplier' => 1,
					));
					$billic->redirect('/Admin/BillingCycles/Name/'.urlencode($_POST['name']).'/');
				}
			}
			$billic->show_errors();
            $billic->modules['FormBuilder']->output(array(
                'form' => $form,
                'button' => 'Continue',
            ));
			return;
		}
		
		if (isset($_GET['Delete'])) {
			$count = $db->q('SELECT COUNT(*) FROM `services` WHERE `billingcycle` = ?', urldecode($_GET['Delete']));
			if ($count[0]['COUNT(*)']>0) {
				$billic->error('Unable to delete the Billing Cycle "'.safe($_GET['Delete']).'" because it has services using to it');
			} else {
				$db->q('DELETE FROM `billingcycles` WHERE `name` = ?', urldecode($_GET['Delete']));
				$billic->status = 'deleted';
			}
		}
		
		$total = $db->q('SELECT COUNT(*) FROM `billingcycles` WHERE `import_hash` = \'\'');
		$total = $total[0]['COUNT(*)'];
		$pagination = $billic->pagination(array(
            'total' => $total,
        ));
		echo $pagination['menu'];
		$billingcycles = $db->q('SELECT * FROM `billingcycles` WHERE `import_hash` = \'\' ORDER BY `seconds` ASC LIMIT '.$pagination['start'].','.$pagination['limit']);

		$billic->set_title('Admin/Billing Cycles');
		echo '<h1><i class="icon-clock"></i> Billing Cycles</h1>';
		echo '<a href="New/" class="btn btn-success"><i class="icon-plus"></i> New Billing Cycle</a>';
		$billic->show_errors();
		echo '<div style="float: right;padding-right: 40px;">Showing ' . $pagination['start_text'] . ' to ' . $pagination['end_text'] . ' of ' . $total . ' Billing Cycles</div>';
		echo '<table class="table table-striped"><tr><th>Name</th><th>Display Name 1</th><th>Display Name 2</th><th>Seconds</th><th>Price Multiplier</th><th>Discount</th><th>Actions</th></tr>';
		if (empty($billingcycles)) {
			echo '<tr><td colspan="20">No Billing Cycles matching filter.</td></tr>';
		}
		foreach($billingcycles as $billingcycle) {
			echo '<tr><td><a href="/Admin/BillingCycles/Name/'.urlencode($billingcycle['name']).'/">'.safe($billingcycle['name']).'</a></td><td>'.$billingcycle['displayname1'].'</td><td>'.$billingcycle['displayname2'].'</td><td>'.$billingcycle['seconds'].'</td><td>'.$billingcycle['multiplier'].'</td><td>'.$billingcycle['discount'].'%</td><td>';
			echo '<a href="/Admin/BillingCycles/Name/'.urlencode($billingcycle['name']).'/" class="btn btn-primary btn-xs"><i class="icon-edit-write"></i> Edit</a>';
			echo '&nbsp;<a href="/Admin/BillingCycles/Delete/'.urlencode($billingcycle['name']).'/"  class="btn btn-danger btn-xs" title="Delete" onClick="return confirm(\'Are you sure you want to delete?\');"><i class="icon-remove"></i> Delete</a>';
			echo '</td></tr>';
		}
		echo '</table>';
	}
}
