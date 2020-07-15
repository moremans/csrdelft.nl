<?php
/**
 * @var \CsrDelft\entity\forum\ForumDraad[] $draden
 */
?>
{{--
# C.S.R. Delft | pubcie@csrdelft.nl
# -------------------------------------------------------------------
# rss.tpl
# -------------------------------------------------------------------
# templaat voor de rss-voederbak
# -------------------------------------------------------------------
--}}
<?xml version = "1.0" encoding = "UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<copyright>Copyright {{date('Y')}} C.S.R. Delft</copyright>
		<pubDate>{{rfc2822('now')}}</pubDate>
		<lastBuildDate>{{rfc2822('now')}}</lastBuildDate>
		<docs>{{CSR_ROOT}}</docs>
		<description>C.S.R. Delft: Vereniging van Christen-studenten te Delft.</description>
		<image>
			<link>{{CSR_ROOT}}</link>
			<title>C.S.R. Delft</title>
			<url>{{CSR_ROOT}}/dist/images/beeldmerk.jpg</url>
			<height>150</height>
			<width>118</width>
			<description>Beeldmerk der C.S.R. Delft</description>
		</image>
		<language>nl-nl</language>
		<atom:link href="{{$privatelink}}" rel="self" type="application/rss+xml"/>
		<link>{{CSR_ROOT}}/forum</link>
		<title>C.S.R. Delft forum recent</title>
		<managingEditor>PubCie@csrdelft.nl (Publiciteitscommissie der C.S.R.)</managingEditor>
		<webMaster>pubcie@csrdelft.nl (Publiciteitscommissie der C.S.R.)</webMaster>
		@foreach($draden as $draad)
			<item>
				<title>{!!rss_encode($draad->titel)!!}</title>
				<link>{{CSR_ROOT}}/forum/reactie/{{$draad->laatste_post_id}}</link>
				<description>{!!rss_encode($draad->getLaatstePostSamenvatting())!!}</description>
				<pubDate>{{rfc2822($draad->laatst_gewijzigd->getTimestamp())}}</pubDate>
				<dc:creator>{!! rss_encode(\CsrDelft\repository\ProfielRepository::getNaam($draad->laatste_wijziging_uid, 'user'))!!}</dc:creator>
				<category>{!!rss_encode($draad->deel->categorie->titel)!!} » {!!rss_encode($draad->deel->titel)!!}</category>
				<comments>{{CSR_ROOT}}/forum/onderwerp/{{$draad->draad_id}}</comments>
				<guid isPermaLink="true">{{CSR_ROOT}}/forum/reactie/{{$draad->laatste_post_id}}
					#{{$draad->laatste_post_id}}</guid>
			</item>
		@endforeach
	</channel>
</rss>
