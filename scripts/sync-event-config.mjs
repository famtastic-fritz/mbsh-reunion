import fs from 'node:fs';
import path from 'node:path';
const root=path.resolve(import.meta.dirname,'..');
const source=fs.readFileSync(path.join(root,'config/site-config.json'),'utf8');
JSON.parse(source);
const check=process.argv.includes('--check');
for(const file of ['frontend/config/site-config.json','backend/config/site-config.json']){
 const target=path.join(root,file);
 if(check){if(fs.readFileSync(target,'utf8')!==source)throw new Error(`Event config drift: ${file}`);}
 else fs.writeFileSync(target,source);
}
console.log(`PASS event config ${check?'consistency':'sync'}`);
