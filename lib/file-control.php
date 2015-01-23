﻿<?php
include("headers.php");
include("settings.php");
$t = $text['file-control'];
?>
<?php if ($_SESSION['githubDiff']) { ?>
<script src="github.js?microtime=<?php echo microtime(true);?>"></script>
<script src="underscore-min.js?microtime=<?php echo microtime(true);?>"></script>
<?php ;}; ?>
<script>
<?php
// Get the save type if any
$saveType = isset($_GET['saveType']) ? strClean($_GET['saveType']) : "";

// Establish the filename/new filename
$file = str_replace("|","/",strClean(
	isset($_POST['newFileName']) && $_POST['newFileName']!=""
	? $_POST['newFileName']
	: $_REQUEST['file']
	));

// Establish the actual name as we may have HTML entities in filename
$file = html_entity_decode($file);

// Put the original $file var aside for use
$fileOrig = $file;

// Trim any +'s or spaces from the end of file
$file = rtrim(rtrim($file,'+'),' ');

// Also remove [NEW] from $file, we can consider $_GET['action'] or $fileOrig to pick that up
$file = rtrim($file,'[NEW]');

// Make each path in $file a full path (; seperated list)
$allFiles = explode(";",$file);
for ($i=0; $i<count($allFiles); $i++) {
	if (strpos($allFiles[$i],$docRoot)===false && $_GET['action']!="getRemoteFile") {
		$allFiles[$i]=str_replace("|","/",$docRoot.$iceRoot.$allFiles[$i]);
	}
};
$file = implode(";",$allFiles);

// Establish the $fileLoc and $fileName (used in single file cases, eg opening. Multiple file cases, eg deleting, is worked out in that loop)
$fileLoc = substr(str_replace($docRoot,"",$file),0,strrpos(str_replace($docRoot,"",$file),"/"));
$fileName = basename($file);

// Check through all files to make sure they're valid/safe
$allFiles = explode(";",$file);
for ($i=0; $i<count($allFiles); $i++) {

	// Uncomment to alert and console.log the action and file, useful for debugging
	// echo ";alert('".xssClean($_GET['action'],"html")." : ".$allFiles[$i]."');console.log('".xssClean($_GET['action'],"html")." : ".$allFiles[$i]."');";

	// Die if the file requested isn't something we expect
	if(
		// A local folder that isn't the doc root or starts with the doc root
		($_GET['action']!="getRemoteFile" &&
			rtrim($allFiles[$i],"/") !== rtrim($docRoot,"/") &&
			strpos(realpath(rtrim(dirname($allFiles[$i]),"/")),realpath(rtrim($docRoot,"/"))) !== 0
		) ||
		// Or a remote URL that doesn't start http
		($_GET['action']=="getRemoteFile" && strpos($allFiles[$i],"http") !== 0)
		) {
		die("alert('Sorry! - problem with file requested');</script>");
	};
}

// If we're due to open a file...
if ($_GET['action']=="load") {
	echo 'action="load";';

	if (file_exists($file)) {
		$finfo = "text";
		// Determine what to do based on mime type
		if (function_exists('finfo_open')) {
			$finfoMIME = finfo_open(FILEINFO_MIME);
			$finfo = finfo_file($finfoMIME, $file);
			finfo_close($finfoMIME);
		} else {
			$fileExt = explode(" ",pathinfo($file, PATHINFO_EXTENSION));
			$fileExt = $fileExt[0];
			if (array_search($fileExt,array("gif","jpg","jpeg","png"))!==false) {$finfo = "image";};
			if (array_search($fileExt,array("doc","docx","ppt","rtf","pdf","zip","tar","gz","swf","asx","asf","midi","mp3","wav","aiff","mov","qt","wmv","mp4","odt","odg","odp"))!==false) {$finfo = "other";};
		}
		if (strpos($finfo,"text")===0 || strpos($finfo,"empty")!==false) {
			echo 'fileType="text";';
			echo 'top.ICEcoder.shortURL = top.ICEcoder.thisFileFolderLink = "'.$fileLoc."/".$fileName.'";';
			$loadedFile = toUTF8noBOM(file_get_contents($file,false,$context),true);
			echo '</script><textarea name="loadedFile" id="loadedFile">'.str_replace("</textarea>","<ICEcoder:/:textarea>",str_replace("&","&amp;",$loadedFile)).'</textarea><script>';
			// Run our custom processes
			include_once("../processes/on-file-load.php");
		} else if (strpos($finfo,"image")===0) {
			echo 'fileType="image";fileName=\''.$fileLoc."/".$fileName.'\';';
		} else {
			echo 'fileType="other";window.open(\'http://'.$_SERVER['SERVER_NAME'].$fileLoc."/".$fileName.'\');';
		};
	} else {
		echo 'fileType="nothing"; top.ICEcoder.message(\''.$t['Sorry'].', '.$fileLoc."/".$fileName.' '.$t['does not seem...'].'\');';
	}

};

// If we're due to change permissions on a file/folder...
if ($_GET['action']=="perms") {
	if (!$demoMode && is_writable($file)) {
		chmod($file,octdec(numClean($_GET['perms'])));
		// Reload file manager
		echo 'top.ICEcoder.updateFileManagerList(\'chmod\',\''.$fileLoc.'\',\''.$fileName.'\',\''.numClean($_GET['perms']).'\');';
		echo 'action="perms";';
		// Run our custom processes
		include_once("../processes/on-file-dir-perms.php");
	} else {
		echo "action='nothing'; top.ICEcoder.message('".$t['Sorry, cannot change...']." \\n".strClean($file)."');";
	}
	echo 'top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);';
}


?>
if (action=="load") {
	if (fileType=="text") {
		setTimeout(function() {
			if (!top.ICEcoder.content.contentWindow.createNewCMInstance) {
				console.log('<?php echo $t['There was a...']; ?>');
				window.location.reload(true);
			<?php
			if (file_exists($file)) {
			?>
			} else {
				top.ICEcoder.loadingFile = true;
				// Reset the various states back to their initial setting
				selectedTab = top.ICEcoder.openFiles.length;	// The tab that's currently selected

				// Finally, store all data, show tabs etc
				top.ICEcoder.createNewTab();
				top.ICEcoder.cMInstances.push(top.ICEcoder.nextcMInstance);
				top.ICEcoder.setLayout();
				top.ICEcoder.content.contentWindow.createNewCMInstance(top.ICEcoder.nextcMInstance);

				<?php if ($_SESSION['githubDiff']) { ?>
					// If we're in GitHub diff mode and have a split pane display, get the content for the diff pane
					if (top.ICEcoder.githubDiff && top.ICEcoder.splitPane) {
						<?php
						// Get our GitHub relative site path & local path
						$ghRemoteURLPos = array_search($ICEcoder["root"],$ICEcoder['githubLocalPaths']);

						$ghLocalURLPaths = $ICEcoder['githubLocalPaths'];
						$ghLocalPath = $ghLocalURLPaths[$ghRemoteURLPos];

						$ghRemoteURLPaths = $ICEcoder['githubRemotePaths'];
						$ghRemoteURL = $ghRemoteURLPaths[$ghRemoteURLPos];

						$ghRemoteURL = str_replace("https://github.com/","",$ghRemoteURL);
						$ghRemoteURL = str_replace("/","|",$ghRemoteURL);

						// If the file is not in a sub-sub dir of the doc root
						if (!strpos($fileLoc,"/",1)) {
							// The file path is simply the file name in the root
							$ghFilePath = $fileName;
						} else {
							// We need to get rid of the root dir and trailing slash
							$ghFilePath = substr(str_replace($ghLocalPath,"",$fileLoc),1);
							// If it's not within a sub-dir, it's just the filename, otherwise prefix with dir path and pipe
							$ghFilePath = $ghFilePath == "" ? $fileName : $ghFilePath."|".$fileName;
						}
						?>

						top.ICEcoder.filesFrame.contentWindow.frames['processControl'].location.href = "github.php?action=read&repo=<?php echo $ghRemoteURL;?>&filePath=<?php echo $ghFilePath;?>&csrf="+top.ICEcoder.csrf;
					}
				<?php ;}; ?>

				// Set the value & innerHTML of the code textarea to that of our loaded file plus make it visible (it's hidden on ICEcoder's load)
				top.ICEcoder.switchMode();
				cM = top.ICEcoder.getcMInstance();
				cM.setValue(document.getElementById('loadedFile').value);
				top.ICEcoder.savedPoints[top.ICEcoder.selectedTab-1] = cM.changeGeneration();
				top.document.getElementById('content').style.visibility='visible';
				top.ICEcoder.switchTab(top.ICEcoder.selectedTab,'noFocus');
				setTimeout(function(){top.filesFrame.contentWindow.focus();},0);

				// Then clean it up, set the text cursor, update the display and get the character data
				top.ICEcoder.contentCleanUp();
				top.ICEcoder.content.contentWindow['cM'+top.ICEcoder.cMInstances[top.ICEcoder.selectedTab-1]].removeLineClass(top.ICEcoder['cMActiveLinecM'+top.ICEcoder.cMInstances[top.ICEcoder.selectedTab-1]], "background");
				top.ICEcoder['cMActiveLinecM'+top.ICEcoder.selectedTab] = top.ICEcoder.content.contentWindow['cM'+top.ICEcoder.cMInstances[top.ICEcoder.selectedTab-1]].addLineClass(0, "background", "cm-s-activeLine");
				top.ICEcoder.nextcMInstance++;
				top.ICEcoder.openFileMDTs.push('<?php echo $serverType=="Linux" ? filemtime($file) : "1000000"; ?>');
				for (var i=0; i<cM.lineCount(); i++) {
					top.ICEcoder.content.contentWindow.CodeMirror.doFold(cM.getLine(i).indexOf("{")>-1?"brace":"xml",null,"+","-",true)(cM, i);
				}
				top.ICEcoder.loadingFile = false;
			<?php
			;};
			?>
			}
		},4);
	}

	if (fileType=="image") {
		top.document.getElementById('blackMask').style.visibility = "visible";
		top.document.getElementById('mediaContainer').innerHTML = 
			"<canvas id=\"canvasPicker\" width=\"1\" height=\"1\" style=\"position: absolute; margin: 10px 0 0 10px; cursor: crosshair\" onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\"></canvas>" + 
			"<img src=\"<?php echo $fileLoc."/".$fileName;?>\" class=\"whiteGlow\" style=\"border: solid 10px #fff; max-width: 700px; max-height: 500px; background-color: #000; background-image: url('images/checkerboard.png')\" onLoad=\"reducedImgMsg = (this.naturalWidth > 700 || this.naturalHeight > 500) ? ', <?php echo $t['displayed at']; ?> ' + this.width + ' x ' + this.height : ''; document.getElementById('imgInfo').innerHTML += ' (' + this.naturalWidth + ' x ' + this.naturalHeight + reducedImgMsg + ')'; top.ICEcoder.drawCanvasImage(this)\"><br>" +
			"<div class=\"whiteGlow\" style=\"display: inline-block; margin-top: -10px; border: solid 10px #fff; color: #000; background-color: #fff\" id=\"imgInfo\"  onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\">" + 
				"<b><?php echo $fileLoc."/".$fileName;?></b>" + 
			"</div><br>" + 
			"<input type=\"text\" id=\"hexMouseXY\" style=\"border: 1px solid #888; border-right: 0; width: 70px\" onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\"></input>" + 
			"<input type=\"text\" id=\"rgbMouseXY\" style=\"border: 1px solid #888; margin-right: 10px; width: 70px\" onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\"></input>" + 
			"<input type=\"text\" id=\"hex\" style=\"border: 1px solid #888; border-right: 0; width: 70px\" onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\"></input>" + 
			"<input type=\"text\" id=\"rgb\" style=\"border: 1px solid #888; width: 70px\" onmouseover=\"top.ICEcoder.overPopup=true\" onmouseout=\"top.ICEcoder.overPopup=false\"></input>";
	}

	top.ICEcoder.serverMessage();top.ICEcoder.serverQueue("del",0);
}
</script>

// Finally, switch mode in case we have saved, renamed file etc
top.ICEcoder.switchMode();
</script>