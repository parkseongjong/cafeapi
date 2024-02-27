<div>
	<p class="title">※ overview</p>
	<ul>
		<li>Version : <?php echo $version; ?></li>
		<?php
		if ( isset($overview_detail) ) {
			foreach($overview_detail as $k=>$v) {
				?><li><?php echo $v; ?></li><?php
			}
		}
		?>
	</ul>
	<div class="table-container">
		<table class="main">
			<thead>
				<tr>
					<th>Name</th>
					<th>Request Category</th>
					<th>Explanation</th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach($overview as $k=>$v) {
						?><tr>
							<td><?php echo $v['name']; ?></td>
							<td><a href="#<?php echo $k; ?>" title="<?php echo $k; ?>"><?php echo $k; ?></a></td>
							<td>
								<?php
								if ( is_array($v['explanation']) ) {
									echo $v['explanation'][$lang];
								} else {
									echo $v['explanation'];
								} ?>
							</td>
						</tr><?php
					}
				?>
		</table>
	</div>

	<p class="sub_title">※ Common</p>
	<ul>
		<li>Method : <?php echo $request_method; ?></li>
		<li>Request URL : <?php echo $request_url; ?></li>
	</ul>

	<?php
	if ( !empty($flow) ) {
		?><p class="sub_title">※ Flow</p>
		<ul>
			<?php foreach($flow[$lang] as $ins) {
				?><li><?php echo $ins; ?></li><?php
			} ?>
		</ul><?php
	} ?>

</div>


<?php
foreach($overview as $k=>$v) {
	$overview_id = $k;
	?>
		
	<div class="box" id="<?php echo $overview_id; ?>">
		<p class="title">※ <?php echo $overview[$overview_id]['name']; ?></p>
		<p><?php echo is_array($overview[$overview_id]['explanation']) ? $overview[$overview_id]['explanation'][$lang] : $overview[$overview_id]['explanation']; ?></p>

		<?php
		if ( !empty($sample_url[$k]) ) {
			?><p class="sub_title">▶ Sample URL (GET) : </p>
			<span><a href="<?php echo $sample_url[$k];?>" title="test" target="_blank"><?php echo $sample_url[$k]; ?></a></span><br /><?php
		} ?>

		<p class="sub_title">▶ Request Info</p>

		<div class="table-container">
			<table class="main re">
				<thead>
					<tr>
						<?php foreach($request_title as $v) {
							?><th><?php echo $v; ?></th><?php
						} ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach($request_info[$overview_id] as $k=>$v) {
						?><tr>
							<td><?php echo $v['code']; ?></td>
							<td><?php echo $v['type']; ?></td>
							<td><?php echo $v['required']; ?></td>
							<td>
								<?php if ( is_array($v['explanation']) ) {
									if ( isset($v['explanation'][$lang]) ) {
										echo $v['explanation'][$lang];
									} else {
										?><table class="sub"><tbody><?php
										foreach($v['explanation'] as $k2=>$v2) {
											?><tr>
												<td><?php echo $v2['code']; ?></td>
												<td><?php echo $v2['type']; ?></td>
												<td>
													<?php if ( is_array($v2['exp']) ) {
														echo $v2['exp'][$lang];
													} else {
														echo $v2['exp'];
													} ?>
												</td>
												<td><?php echo $v2['ex']; ?></td>
											</tr><?php
										} // foreach
										?></tbody></table><?php
									}
								} else {
									echo $v['explanation'];
								} ?>
							</td>
							<td><?php echo $v['ex']; ?></td>
							<td><?php echo $v['etc']; ?></td>
						</tr><?php
					}
					?>
				</tbody>
			</table>
		</div>
				
		<p class="sub_title">▶ Response Info</p>
		<div class="table-container">
			<table class="main re">
				<thead>
					<tr>
						<?php foreach($response_title as $v) {
							?><th><?php echo $v; ?></th><?php
						} ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach($response_info[$overview_id] as $k=>$v) {
						?><tr>
							<td><?php echo $v['code']; ?></td>
							<td><?php echo $v['type']; ?></td>
							<td>
								<?php if ( is_array($v['explanation']) ) {
									if ( isset($v['explanation'][$lang]) ) {
										echo $v['explanation'][$lang];
									} else {
										?><table class="sub"><tbody><?php
										foreach($v['explanation'] as $k2=>$v2) {
											?><tr>
												<td><?php echo $v2['code']; ?></td>
												<td><?php echo $v2['type']; ?></td>
												<td>
													<?php if ( is_array($v2['exp']) ) {
														echo $v2['exp'][$lang];
													} else {
														echo $v2['exp'];
													} ?>
												</td>
												<td><?php echo $v2['ex']; ?></td>
											</tr><?php
										} // foreach
										?></tbody></table><?php
									}
								} else {
									echo $v['explanation'];
								} ?>
							</td>
							<td><?php echo $v['ex']; ?></td>
							<td><?php echo $v['etc']; ?></td>
						</tr><?php
					}
					?>
				</tbody>
			</table>
		</div>
		
		<p class="sub_title">▶ Error Code</p>
		<div class="table-container">
			<table class="main err">
				<thead>
					<tr>
						<?php foreach($err_title as $v) {
							?><th><?php echo $v; ?></th><?php
						} ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach($error_code[$overview_id] as $k=>$v) {
						?><tr>
							<td><?php echo $k; ?></td>
							<td><?php echo $v['error']; ?></td>
							<td><?php echo $v['msg']; ?></td>
							<td></td>
						</tr><?php
					}
					?>
				</tbody>
			</table>
		</div>
		
		<?php
		if ( !empty($list[$overview_id]) ) { ?>
		<ul>
			<?php foreach($list[$overview_id] as $v) { ?>
				<li><?php echo $v; ?></li>
			<?php } ?>
		</ul>
		<?php } ?>

	</div>
	<?php

}
?>

