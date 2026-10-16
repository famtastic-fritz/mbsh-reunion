import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const root = path.resolve(import.meta.dirname, '..');
const out = path.join(root, 'frontend/assets/mixer-2026');
const source = path.join(out, 'source');
const wide = path.join(source, 'miami-evening-harry-wide-v1.png');
const mobile = path.join(source, 'miami-evening-harry-mobile-v1.png');
const data = async p => `data:image/png;base64,${(await fs.readFile(p)).toString('base64')}`;
const bg = await data(wide), portrait = await data(mobile);
const seal = await data(path.join(root,'frontend/assets/brand-mark/brand-mark.png'));
const credit = await data(path.join(root,'frontend/assets/famtastic/famtastic-designs-logo-v1.png'));
const xml = s => s.replaceAll('&','&amp;').replaceAll('<','&lt;');
const text = (x,y,copy,size=24,color='#f8f1e6',family='Arial',weight=400,extra='') => `<text x="${x}" y="${y}" fill="${color}" font-family="${family}" font-size="${size}" font-weight="${weight}" ${extra}>${xml(copy)}</text>`;
const medallion = (x,y,size) => `<defs><clipPath id="seal"><circle cx="${x+size/2}" cy="${y+size/2}" r="${size*.46}"/></clipPath></defs><image href="${seal}" x="${x}" y="${y}" width="${size}" height="${size}" clip-path="url(#seal)"/>`;
const footer=(w,h,url=true)=>`<rect x="0" y="${h-96}" width="${w}" height="96" fill="#080a08"/>${url?text(w/2,h-69,'mbsh96reunion.com',20,'#edcf91','Arial',700,'text-anchor="middle"'):''}<image href="${credit}" x="${w/2-80}" y="${h-54}" width="160" height="53"/>`;
const svg=(w,h,body)=>Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${body}</svg>`);
await sharp(wide).resize(1920,1080).webp({quality:88}).toFile(path.join(out,'hero-wide.webp'));
await sharp(mobile).resize(900,1600).webp({quality:88}).toFile(path.join(out,'hero-mobile.webp'));
// Controlled type and original seal over generated photography; final text is never AI lettering.
for (const [name,w,h] of [['announcement-square',1080,1080],['announcement-feed',1080,1350],['announcement-story',1080,1920]]) {
 const story=h>1500;
 let body=`<defs><linearGradient id="topshade" x2="0" y2="1"><stop stop-color="#080607" stop-opacity=".94"/><stop offset=".42" stop-color="#080607" stop-opacity=".88"/><stop offset=".72" stop-color="#080607" stop-opacity="0"/></linearGradient></defs><image href="${portrait}" width="${w}" height="${h}" preserveAspectRatio="xMidYMid slice"/><rect width="${w}" height="${h-96}" fill="url(#topshade)"/>`;
 body+=text(64,story?265:80,'NEW VENUE · UPDATED TICKET PRICE',23,'#e7c980','Arial',700);
 body+=text(64,story?325:142,'MBSH CLASS OF 1996',26,'#f8f1e6','Arial',700);
 body+=text(60,story?413:228,'Reunion Mixer',76,'#fff9ee','Georgia',700);
 body+=text(64,story?482:300,'TWISTED TONIC',43,'#e7c980','Arial',700);
 body+=text(64,story?540:355,'SATURDAY, NOVEMBER 7, 2026',28,'#f8f1e6','Arial',700);
 body+=text(64,story?590:402,'7–10 PM · MIAMI BEACH',28);
 body+=text(60,story?697:495,'$30',86,'#fff9ee','Georgia',700)+text(245,story?690:488,'PER PERSON',24,'#e7c980','Arial',700);
 body+=text(64,story?745:548,'Food on your own',29);
 body+=medallion(850,story?280:95,160);
 if(story) body+=`<rect x="64" y="1480" width="952" height="80" rx="10" fill="#080607" opacity=".9"/>`+text(540,1530,'TICKETS: mbsh96reunion.com',28,'#edcf91','Arial',700,'text-anchor="middle"');
 body+=footer(w,h);
 await sharp(svg(w,h,body)).jpeg({quality:94,mozjpeg:true}).toFile(path.join(out,`${name}.jpg`));
}
let body=`<image href="${bg}" width="1200" height="630"/><rect width="720" height="534" fill="#080607" opacity=".8"/>`;
body+=text(45,70,'MBSH CLASS OF 1996 · 30 YEARS',20,'#e7c980','Arial',700)+text(42,155,'Reunion Mixer',60,'#fff9ee','Georgia',700)+text(45,218,'TWISTED TONIC',33,'#e7c980','Arial',700)+text(45,276,'NOVEMBER 7, 2026 · 7–10 PM',25)+text(45,362,'$30 per person',47,'#fff9ee','Georgia',700)+text(45,411,'Food on your own',25)+text(45,480,'New venue. Updated ticket price.',20,'#e7c980')+medallion(1000,28,150)+footer(1200,630);
await sharp(svg(1200,630,body)).jpeg({quality:94}).toFile(path.join(out,'social-preview.jpg'));
body=`<image href="${bg}" width="1600" height="900"/><rect width="1030" height="804" fill="#080607" opacity=".85"/>`;
body+=text(60,100,'MBSH CLASS OF 1996 · 30TH REUNION',26,'#e7c980','Arial',700)+text(56,207,'Reunion Mixer',84,'#fff9ee','Georgia',700)+text(60,296,'TWISTED TONIC',45,'#e7c980','Arial',700)+text(60,368,'SATURDAY, NOVEMBER 7, 2026',30)+text(60,428,'7–10 PM EASTERN · MIAMI BEACH',28)+text(60,520,'$30 ADMISSION · FOOD ON YOUR OWN',30,'#e7c980','Arial',700)+text(60,603,'723 Lincoln Ln N · Inside The Lincoln Eatery',25)+text(60,709,'YOUR LIVE ADMISSION CODE APPEARS IN YOUR PRIVATE WALLET',20,'#d3c8be')+footer(1600,900);
await sharp(svg(1600,900,body)).png().toFile(path.join(out,'mixer-ticket.png'));
await sharp(svg(1600,900,body)).webp({quality:90}).toFile(path.join(out,'mixer-ticket.webp'));
body='<rect width="1080" height="1080" fill="#100b0e"/><rect x="48" y="48" width="984" height="880" rx="12" fill="none" stroke="#d8b86a"/>';
body+=text(80,120,'MBSH CLASS OF 1996 · REUNION MIXER',24,'#e7c980','Arial',700)+text(76,228,'Meet us at',65,'#fff9ee','Georgia',700)+text(76,310,'Twisted Tonic.',65,'#fff9ee','Georgia',700)+text(80,406,'SATURDAY, NOVEMBER 7, 2026',30,'#e7c980','Arial',700)+text(80,465,'7–10 PM · $30 per person',34)+text(80,538,'Food on your own',30)+text(80,645,'Inside The Lincoln Eatery',28,'#e7c980')+text(80,700,'723 Lincoln Ln N',36)+text(80,755,'Miami Beach, FL 33139',32)+text(80,850,'Directions and tickets: mbsh96reunion.com',25)+medallion(800,170,160)+footer(1080,1080);
await sharp(svg(1080,1080,body)).png().toFile(path.join(out,'location-card.png'));
console.log('Rendered 8 mixer graphics from two preserved generation masters.');
