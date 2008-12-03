{*
** media item in the object container's detail page
*}

{assign var="thumbWidth" 		value = 130}
{assign var="thumbHeight" 		value = 98}
{assign var="filePath"			value = $item.path}
{assign var="fileName"			value = $item.filename|default:$item.name}
{assign var="fileTitle"			value = $item.title}
{assign var="newPriority"		value = $item.priority+1|default:$priority}
{assign var="mediaPath"         value = $conf->mediaRoot}
{assign var="mediaUrl"          value = $conf->mediaUrl}

{assign_concat var="linkUrl"            0=$html->url('/multimedia/view/') 1=$item.id}

{assign_concat var="imageAltAttribute"	0="alt='"  1=$item.title 2="'"}
{assign_concat var="mediaCacheBaseURL"	0=$conf->mediaUrl  1="/" 2=$conf->imgCache 3="/"}
{assign_concat var="mediaCachePATH"		0=$conf->mediaRoot 1=$conf->DS 2=$conf->imgCache 3=$conf->DS}

{assign_associative var="params" width=$thumbWidth height=$thumbHeight longside=false mode="fill" modeparam="000000" type=null upscale=false}
{assign_associative var="htmlAttr" width=$thumbWidth height=$thumbHeight alt=$item.title title=$item.name}

{strip}

	<input type="hidden" name="data[RelatedObject][{$relation}][{$item.id}][id]" value="{$item.id}" />
	<input type="hidden" name="data[RelatedObject][{$relation}][{$item.id}][modified]" value="0" />
	
	<div style="width:{$thumbWidth}px; height:{$thumbHeight}px" class="imagebox">
	{if strtolower($item.ObjectType.name) == "image"}

		{$beEmbedMedia->object($item,$params,$htmlAttr)}
		
	{elseif ($item.provider|default:false)}
	
		{assign_concat var="myStyle" 0="width:" 1=$conf->videoThumbWidth 2="px; " 3="height:" 4=$conf->videoThumbHeight 5="px;"}
		{assign_associative var="attributes" style=$myStyle}
		{$beEmbedMedia->object($item,null,$attributes)}
	
	{elseif strtolower($item.ObjectType.name) == "audio"}
	
		<a href="{$linkUrl}"><img src="{$session->webroot}img/iconset/88px/audio.png" /></a>	
	
	{else}
		
		<img src="/img/iconset/88px/text.png" />
		{*
		<a href="{$conf->mediaUrl}{$filePath}" target="_blank"><img src="{$session->webroot}img/mime/{$item.mime_type}.gif" /></a>
		*}
	{/if}
	
	</div>
	

	
	<label class="evidence">
		<input type="text" class="priority" style="text-align:left; margin-left:0px;"
		name="data[RelatedObject][{$relation}][{$item.id}][priority]" value="{$item.priority|default:$priority}" size="3" maxlength="3"/>
	</label>


	<ul class="info_file_item">
		<li>
			<input class="info_file_item" style="border:0px;" type="text" value="{$item.title|escape:'htmlall'|default:""}" name="data[RelatedObject][{$relation}][{$item.id}][title]" />
		</li>
		<li>
			<textarea class="info_file_item" style="border:0px; border-bottom:1px solid silver;" name="data[RelatedObject][{$relation}][{$item.id}][description]">{$item.description|default:""}</textarea>
			<br />
			<a rel="{$linkUrl} #multimediaiteminside" class="modalbutton">details</p>
			
			<a style="margin-left:60px" href="javascript: void(0);" onclick="removeItem('item_{$item.id}')" >delete</a>
			<!-- <img style="vertical-align:middle;" src="{$session->webroot}img/iconClose.png" > -->
		</li>
	</ul>


{/strip}
