<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form">
	<div id="form-body">
		<div class="logout-button">
				<input type="submit" name="Submit" class="button" value="<?php echo JText::_('JLOGOUT'); ?>" />
				<input type="hidden" name="option" value="com_users" />
				<input type="hidden" name="task" value="user.logout" />
				<input type="hidden" name="return" value="<?php echo $return; ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</div>
	</div>
</form>
