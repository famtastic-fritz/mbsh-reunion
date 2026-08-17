<?php

if(!defined('ABSPATH')){fwrite(STDERR,"Run with wp eval-file.\n");exit(1);}

$components=[
 ['Cinematic Hero','cinematic_hero','home','Public','RSVP now','/rsvp.html'],
 ['Event Readiness','event_readiness','home','Public','',''],
 ['Featured Memory Reel','memory_reel','home','Public','Share a memory','/through-years.html'],
 ['Event Details','event_details','home','Public','View event guide','/rsvp.html'],
 ['RSVP Form','rsvp_form','rsvp','Public','Complete RSVP','/rsvp.html'],
 ['Dinner Selection','dinner_form','menu','Registered attendees','Choose dinner','/menu/'],
 ['Ticket Offer','ticket_offer','tickets','Public','Reserve tickets','/tickets.html'],
 ['Sponsor Wall','sponsor_wall','tickets','Public','Become a sponsor','/tickets.html#sponsor'],
 ['Memory Archive','memory_archive','through-the-years','Public','Add your chapter','/through-years.html#share'],
 ['Tribute Film','tribute_film','in-memory','Public','Submit a tribute','/memorial.html'],
 ['Hi-Tide Harry Prompt','harry_prompt','global','Public','',''],
 ['FAQ Group','faq_group','global','Public','Ask Harry','/portal/'],
];
foreach($components as [$title,$type,$page,$visibility,$cta,$url]){
 $existing=get_posts(['post_type'=>'reunion_component','post_status'=>'any','title'=>$title,'numberposts'=>1]);
 $id=$existing?(int)$existing[0]->ID:(int)wp_insert_post(['post_type'=>'reunion_component','post_status'=>'draft','post_title'=>$title,'post_content'=>'Structured FAMtastic component. Edit its content, placement, audience, schedule and call to action before publishing.']);
 update_post_meta($id,'_famtastic_component_type',$type);update_post_meta($id,'_famtastic_visibility',$visibility);update_post_meta($id,'_famtastic_cta_label',$cta);update_post_meta($id,'_famtastic_cta_url',$url);update_post_meta($id,'_famtastic_owner','Site Owner');
 wp_set_object_terms($id,$page,'reunion_component_page',false);
}

$faqs=[
 ['When and where is the reunion?','The 30th reunion is Saturday, November 7, 2026 at Miami Shores Country Club. Doors open at 6:30 PM.'],
 ['How do I update my RSVP or dinner choice?','Sign into the attendee portal. Your RSVP and dinner selections remain connected to your verified account.'],
 ['How do I share a photo or memory?','Use My Memories in the attendee portal. Uploads remain private until the committee reviews the content and publication permission.'],
];
foreach($faqs as [$question,$answer]){if(!get_page_by_title($question,OBJECT,'reunion_faq'))wp_insert_post(['post_type'=>'reunion_faq','post_status'=>'publish','post_title'=>$question,'post_content'=>$answer]);}

echo "Seeded ".count($components)." component definitions and ".count($faqs)." FAQs.\n";
