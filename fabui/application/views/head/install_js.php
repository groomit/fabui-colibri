<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 
?>
<script type="text/javascript">
	var selected_head = "<?php echo $head?>";
	
	$(function () {
		
		$("#heads").on('change', set_head_img);
		$("#heads").trigger('change');
		$("#set-head").on('click', set_head);
		
		
		<?php if(isset($_REQUEST['head_installed']) && $units['hardware']['head'] != 'mill_v2'): ?>
			
			$.SmartMessageBox({
				title : "<i class='fa fa-warning'></i> New head has been installed, it is recommended to repeat the Probe Calibration operation",
				buttons : '[<i class="fa fa-crosshairs"></i> Calibrate][Ignore]'
			}, function(ButtonPressed) {
				if(ButtonPressed === "Calibrate") {	
						document.location.href="<?php echo site_url('maintenance/probe-length-calibration'); ?>";
						location.reload();
				}
				if (ButtonPressed === "Ignore") {
					
				}
		
			});
		
		<?php endif; ?>
		
		$('.settings-action').on('click', buttonAction);
		$('.capability').on('change', capability_change);
		$("#inputId").on('change', importHeadSettings);
		initFieldValidation();
		
	});

	function initFieldValidation()
	{
		$("#head-settings").validate({
			rules:{
				name:{
					required:true
				},
				'capability[]': {
					required: true,
					minlength: 1
				}
			},
			messages: {
				name:{
					required: _("Please enter head name")
				},
				'capability[]':  _("Please select at least one capability")
			},
			  submitHandler: function(form) {
				console.log("FORM SUBMIT");
			},
			errorPlacement : function(error, element) {
				if(element[0].name == "capability[]")
				{
					error.insertAfter( $("#capabilities-container") );
				}
				else
					error.insertAfter(element.parent());
			}
		});
		
		$("#head-name").inputmask("Regex");
	}

	 function set_head_img(){
	 	
	 	selected_head = $(this).val();
	 	
	 	if(heads.hasOwnProperty(selected_head))
	 	{
			$("#edit-button").show();
			$("#remove-button").show();
			var head = heads[selected_head];
			if( head.fw_id < 100 )
				$("#remove-button").hide();
		}
		else
		{
			$("#edit-button").hide();
			$("#remove-button").hide();
		}
	 	
	 	$(".jumbotron").html('');
	 	
	 	$("#head_img").parent().attr('href', 'javascript:void(0);');
	 	$("#head_img").css('cursor', 'default');
	 	$("#set-head").prop("disabled",false);
	 	
		$("#head_img").attr('src', '/assets/img/head/' + $(this).val() + '.png');
		
		if($("#" + $(this).val() + "_description").length > 0){
			$(".jumbotron").html($("#" + $(this).val() + "_description").html());
		}
		
		if($(this).val() == 'more_heads'){
			$("#head_img").parent().attr('href', 'https://store.fabtotum.com?from=fabui&module=maintenance&section=head');
	 		$("#head_img").css('cursor', 'pointer');
	 		$("#set-head").prop("disabled",true);
		}
		
		if($(this).val() == 'head_shape'){
			$("#set-head").prop("disabled",true);
		}
	 }

	function set_head(){
	 	if($("#heads").val() == 'head_shape'){
	 		alert('Please select a Head');
	 		return false;
	 	}
	 	
	 	openWait('<i class="fa fa-circle-o-notch fa-spin"></i> Installing head');
	 	
	 	$.ajax({
			type: "POST",
			url: "<?php echo site_url("head/setHead") ?>/"+ $("#heads").val(),
			dataType: 'json'
		}).done(function( data ) {
			
			$(".alerts-container").find('div:first-child').remove();
			$(".alerts-container").append('<div class="alert alert-success animated  fadeIn" role="alert"><i class="fa fa-check"></i> Well done! Now your <strong>FABtotum Personal Fabricator</strong> is set for the <strong>'+ data.name +'</strong>.</div>');
			
			//waitContent('Well done! Now your <strong><i>FABtotum Personal Fabricator</i></strong> is configured to use <strong><i>'+ data.name+'</i></strong>.');
			
			setTimeout(function(){
					document.location.href =  '<?php echo site_url('head'); ?>?head_installed';
					location.reload();
				}, 2000);
			
		});
	}
	
	function capability_change(update_working_mode=true)
	{
		var capabilities = [];
		
		$(".capability").each(function (index, value) {
			if($(this).is(":checked"))
			{
				capabilities.push($(this).attr('data-attr'));
			}
		});
		
		var working_mode = 3;
		
		if(capabilities.indexOf("print") > -1)
		{
			$(".nozzle-settings").slideDown();
			working_mode = 1;
		}
		else
			$(".nozzle-settings").slideUp();
			
		if(capabilities.indexOf("mill") > -1)
		{
			$(".motor-settings").slideDown();
			if(working_mode == 1)
				working_mode = 0;
			else
				working_mode = 3;
		}
		else
			$(".motor-settings").slideUp();
			
		if(capabilities.indexOf("feeder") > -1)
			$(".feeder-settings").slideDown();
		else
			$(".feeder-settings").slideUp();
			
		
		if(capabilities.indexOf("laser") > -1)
			working_mode = 2;
			
		if(capabilities.indexOf("scan") > -1)
			working_mode = 4;
		
		if(update_working_mode)
			$("#head-working_mode").val(working_mode);

	}
	
	function buttonAction(){
		var action = $(this).attr('data-action');
		console.log('action:', action);
		
		switch(action)
		{
			case "edit":
				if(heads.hasOwnProperty(selected_head))
				{
					populateHeadSettings(heads[selected_head]);
				}
				$('#settingsModal').modal('show');
				break;
			case "add":
				document.getElementById("head-settings").reset();
				$(".url-container").show();
				$(".description-container").show();
				$("#head-name").removeAttr("readonly");
				$("#head-fw_id").removeAttr("readonly");
				$('#settingsModal').modal('show');
				break;
			case "remove":
				removeHeadSettings();
				break;
			case "save":
				if($("#head-settings").valid())
					saveHeadSettings();
				break;
			case "import":
				$("#inputId").trigger('click');
				break;
			case "export":
				if($("#head-settings").valid())
					exportHeadSettings();
				break;
		}
		
		return false;
	}
	
	function getHeadSettings()
	{
		var capabilities = [];
		
		var settings = {};
		
		$("#head-settings :input").each(function (index, value) {
			var name = $(this).attr('name');
			var type = $(this).attr('type');
			if(name)
			{
				if(type == 'checkbox')
				{
					if($(this).is(":checked"))
					{
						capabilities.push( $(this).attr('data-attr') );
						console.log('CHECK', $(this).attr('data-attr') );
					}
				}
				else
				{
					console.log('INPUT', name);
					settings[name] = $(this).val();
				}
				
				if(name == "custom_gcode")
					settings[name] = settings[name].toUpperCase();
			}
		});
		
		settings['capabilities'] = capabilities;
		
		console.log(settings);
		
		//console.log();
		return settings;
	}
	
	function populateHeadSettings(head)
	{
		document.getElementById("head-settings").reset();

		console.log(head);
		for (var key in head) {
			var value = head[key];
			// now you can use key as the key, value as the... you guessed right, value
			if(Array.isArray(value))
			{
				for(var i=0; i<value.length; i++)
				{
					var id = "#cap-" + value[i];
					$(id).prop('checked', true);
				}
			}
			else
			{
				var id = "#head-"+key;
				$(id).val(value);
				console.log('try to', id);
			}
		}
		
		capability_change(false);
		/**
		* only for fabtotums official heads
		*/
		if(head.fw_id < 100){
			$(".url-container").hide();
			$(".description-container").hide();
			$("#head-name").attr("readonly", "readonly");
			$("#head-fw_id").attr("readonly", "readonly");
			
		}

	}
	
	function saveHeadSettings()
	{
		console.log('saveHeadSettings');
		var settings = getHeadSettings();
		
		var filename = settings['name'].replace(/ /g, "_").toLowerCase();
		console.log('filename', filename);
		
		$.ajax({
			type: 'post',
			url: '<?php echo site_url('head/saveHead'); ?>/' + filename,
			data : settings,
			dataType: 'json'
		}).done(function(response) {
			console.log(response);
			fabApp.showInfoAlert('<strong>{0}</strong> saved'.format(settings.name));
			setTimeout(function(){
				location.reload();
			}, 1000);
		});
	}
	
	function exportHeadSettings()
	{
		var settings = getHeadSettings();
		var filename = settings['name'].replace(/ /g, "_").toLowerCase() + ".json";
		
		var content = JSON.stringify(settings, null, 2)
		var blob = new Blob([content], {type: "text/plain"});
		saveAs(blob, filename);
	}
	
	function importHeadSettings(event)
	{
		var input = event.target;
		var reader = new FileReader();
		reader.onload = function(){
			var text = reader.result;
			
			content = jQuery.parseJSON(text);
			populateHeadSettings(content);
		}
		reader.readAsText(input.files[0]);
		
		return false;
	}
	
	function removeHeadSettings()
	{
		$.SmartMessageBox({
			title: "<?php echo _("Attention");?>!",
			content: "<?php echo _("Remove <strong>{0}</strong> settings?");?>".format(heads[selected_head].name),
			buttons: '[<?php echo _("No")?>][<?php echo _("Yes")?>]'
		}, function(ButtonPressed) {
		   
			if (ButtonPressed === "<?php echo _("Yes")?>")
			{
				console.log("Remove head confirmation");
				$.ajax({
					type: 'post',
					url: '<?php echo site_url('head/removeHead'); ?>/' + selected_head,
					dataType: 'json'
				}).done(function(response) {
					console.log(response);
					fabApp.showInfoAlert('<strong>{0}</strong> removed'.format(heads[selected_head].name));
					setTimeout(function(){
						location.reload();
					}, 1000);
				});
			}
			if (ButtonPressed === "<?php echo _("No")?>")
			{
				
			}
		});
	}
	
	
	
</script>
