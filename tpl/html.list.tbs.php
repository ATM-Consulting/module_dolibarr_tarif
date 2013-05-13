<table width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;">
		<tr>
			[onshow;block=begin; when [liste.noheader]==0]
			<td class="nobordernopadding" width="40" align="left" valign="middle">
				[liste.image;magnet=img; strconv=no]
			</td>
			<td class="nobordernopadding"><div class="titre">[liste.titre]</div></td>
			[onshow;block=end]
			<td class="nobordernopadding" align="right" valign="middle">
				<div> 
				<!-- [onshow;block=div;when [pagination.last]+-1 ] -->
				    <a class="precedent" href="javascript:TListTBS_GoToPage('[liste.id]',[pagination.prev])"><!-- [pagination.prev;endpoint;magnet=a] -->[liste.picto_precedent;strconv=no]</a>
				    Page
				    <a class="page" href="javascript:TListTBS_GoToPage('[liste.id]',[pagination.page;navsize=15;navpos=centred])"> [pagination.page;block=a] </a>
				    <u>[pagination.page;block=u;currpage]</u>
				    <a class="suivant" href="javascript:TListTBS_GoToPage('[liste.id]',[pagination.next])"><!-- [pagination.last;endpoint;magnet=a] -->[liste.picto_suivant;strconv=no]</a>
				</div>
			</td>
		</tr>
</table>	

<table id="[liste.id]" class="liste" width="100%">
	<tr class="liste_titre">
		<td class="liste_titre" style="border-bottom: 1px solid #AAAAAA;">[entete.libelle;block=td;strconv=no] 
			<span>[onshow;block=span; when [entete.order]==1]<a href="javascript:TListTBS_OrderDown('[liste.id]','[entete.$;strconv=js]')">[liste.order_down;strconv=no]</a><a href="javascript:TListTBS_OrderUp('[liste.id]', '[entete.$;strconv=js]')">[liste.order_up;strconv=no]</a></span>
		</td>
	</tr>
	<tr class="liste_titre">[onshow;block=tr;when [liste.nbSearch]+-0]
		<td class="liste_titre" style="border-bottom: 1px solid #AAAAAA;">[recherche.val;block=td;strconv=no]</td>
	</tr>
	<tr class="impair">
		<!-- [champs.$;block=tr;sub1] -->
		<td style="border-bottom: 1px solid #AAAAAA;">[champs_sub1.val;block=td; strconv=no]</td>
	</tr>
	<tr class="pair">
		<!-- [champs.$;block=tr;sub1] -->
		<td style="border-bottom: 1px solid #AAAAAA;">[champs_sub1.val;block=td; strconv=no]</td>
	</tr>
</table>
<p align="center">
	[liste.messageNothing] [onshow; block=p; strconv=no; when [liste.totalNB]==0]
</p>
	