# 1996 trivia starter — research record

Prepared October 22, 2026, America/New_York (source access record: October 23, 2026 UTC).

**Classification: candidate.** This lane authored a source-checked question bank and its research record. It did not import data into production, open a round, create a player or score, publish results, send email, or spend graphics credits. Agent fact-checking is complete; committee review and operational acceptance remain pending.

## Deliverable and scope

- Bank: [`data/trivia/1996-starter.json`](../../data/trivia/1996-starter.json).
- Bank identity: `class-of-1996-starter-v1`; schema version `1`.
- Bank SHA-256: `9de3ea8763c29c7eb92c361cc005edf50c2abb29977dff8788b140c6f5915483`.
- 30 original multiple-choice questions, four distinct choices each, one correct answer and an explanation.
- Every question carries a stable key, category, `event_year: 1996`, a narrower `event_scope`, 100 points, and per-claim source metadata.
- The files belong in server-side source and staff review. Answers, explanations and source titles must not be exposed in an attendee payload before the relevant reveal. Do not copy the JSON bank into the public frontend.

| Category | Questions |
| --- | ---: |
| Music | 8 |
| Film & TV | 8 |
| Sports | 7 |
| Culture & Events | 7 |

The bank uses general cultural history. It contains no school-specific assertions, classmate information, private photographs, copied commercial question-bank text, lyric excerpts, invented achievements, prizes or deadlines.

## Date and wording decisions

The year identifies the event named in each question. Awards questions explicitly name the ceremony year. The Oscar prompt also explains that the 1996 ceremony honored 1995 films. Grammys questions do not label the winning recordings as 1996 releases. Sports season boundaries are explicit; the January Super Bowl is not confused with the following season's championship.

International launches are scoped: Pokémon asks for the original Japanese pair, while the Nintendo question specifies its US launch. Tamagotchi asks about its first release rather than every country's release. The scientific and technology questions distinguish birth from announcement, launch from landing, a single chess game from an entire match, and an organization's founding from a later service launch.

Question wording, distractor choices and short explanations were authored for this bank. Source material was used to verify discrete facts, with no long quotations or reused images.

## Verification method and source register

Research used live web search plus direct opening of official awards databases, studios, a film institute, sports governing bodies, artists, manufacturers, an institutional history, NASA, IBM and a preserved organizational press release. Each JSON source row records the exact claim supported and its access timestamp. The question identifiers in the table are an audit map, not a public answer key.

27 of the 28 unique final URLs yielded directly opened page or PDF content. The World Athletics page for `1996-sports-johnson-double` returned HTTP 403 on direct opening. Its explicit primary-source indexed excerpt states the double; separate indexed World Athletics results and institutional retrospectives corroborated it. That source row is honestly marked `indexed_primary_excerpt`, not `opened_page`. Its claim is well supported, but fresh full-page retrieval remains a source-access limitation.

Several initially considered sources had maintenance screens, redirects, regional errors or oversized PDFs. They were replaced where necessary: the final Mission: Impossible URL uses Paramount's current domain; Bandai's accessible English PDF supports the original release year; the Internet Archive's own press release is preserved by Library Technology Guides. A Sabrina draft topic was replaced with a directly accessible Emmy record. Wikipedia, social posts and unsourced trivia lists were not used as final evidence.

| Question key(s) | Source checked | Method |
| --- | --- | --- |
| `1996-music-wannabe` | [Official Charts — Wannabe 25th anniversary](https://www.officialcharts.com/chart-news/spice-girls-announce-wannabe-25th-anniversary-ep-featuring-previously-unreleased-song-feed-your-love__33356/) | opened_page |
| `1996-music-macarena` | [Recording Academy — Los Del Río and Macarena](https://www.grammy.com/news/feature-remember-when-los-del-rio-macarena-take-over-us/) | opened_page |
| `1996-music-killing-me-softly` | [Official Charts — Best-selling singles of 1996](https://www.officialcharts.com/chart-news/official-top-40-best-selling-singles-of-1996__33496/) | opened_page |
| `1996-music-falling-into-you` | [Céline Dion — Falling Into You](https://www.celinedion.com/discography/falling-into-you/) | opened_page |
| `1996-music-grammy-album`, `1996-music-grammy-record` | [Recording Academy — 38th Annual Grammy Awards, 1996](https://www.grammy.com/awards/38th-annual-grammy-awards/) | opened_page |
| `1996-music-unbreak-my-heart` | [Official Charts — Un-Break My Heart](https://www.officialcharts.com/songs/toni-braxton-un-break-my-heart/) | opened_page |
| `1996-music-no-diggity` | [Official Charts — No Diggity](https://www.officialcharts.com/songs/blackstreet-ft-dr-dre-no-diggity/) | opened_page |
| `1996-film-independence-day` | [20th Century Studios — Independence Day](https://www.20thcenturystudios.com/movies/independence-day) | opened_page |
| `1996-film-space-jam` | [BFI Southbank — Space Jam](https://whatson.bfi.org.uk/Online/default.asp?BOparam%3A%3AWScontent%3A%3AloadArticle%3A%3Acontext_id=&BOparam%3A%3AWScontent%3A%3AloadArticle%3A%3Apermalink=spacejam2022) | opened_page |
| `1996-film-scream-director` | [BFI — Scream (1996)](https://www.bfi.org.uk/film/1bd1f2a9-138e-5279-87fa-ae4b8a3bdd6b/scream) | opened_page |
| `1996-film-matilda` | [Sony Pictures — Matilda](https://www.sonypictures.com/movies/matilda) | opened_page |
| `1996-film-mission-impossible` | [Paramount — Mission: Impossible](https://www.paramount.com/movies/mission-impossible) | opened_page |
| `1996-tv-lithgow-emmy` | [Television Academy — 1996 Lead Actor in a Comedy Series](https://www.televisionacademy.com/awards/nominees-winners/1996/outstanding-lead-actor-in-a-comedy-series) | opened_page |
| `1996-film-best-picture` | [Academy — 68th Academy Awards, 1996](https://www.oscars.org/oscars/ceremonies/1996) | opened_page |
| `1996-tv-comedy-emmy` | [Television Academy — 1996 Outstanding Comedy Series](https://www.televisionacademy.com/awards/nominees-winners/1996/outstanding-comedy-series) | opened_page |
| `1996-sports-bulls` | [NBA — 72-win season](https://www.nba.com/news/history-top-moments-bulls-win-72-games) | opened_page |
| `1996-sports-olympic-city`, `1996-sports-olympic-cauldron` | [Muhammad Ali Center — Ali Takes the Torch](https://alicenter.org/ali-takes-the-torch/) | opened_page |
| `1996-sports-johnson-double` | [World Athletics — Michael Johnson's Olympic double](https://worldathletics.org/news/news/michael-johnson-to-target-own-400m-record) | indexed_primary_excerpt |
| `1996-sports-world-series` | [MLB — 1996 postseason](https://www.mlb.com/postseason/history/1996) | opened_page |
| `1996-sports-super-bowl` | [Pro Football Hall of Fame — 1996](https://www.profootballhof.com/football-history/football-history/1980-1999/1996) | opened_page |
| `1996-sports-panthers-final` | [NHL — Morning Skate, June 4, 2024, page 3](https://media.nhl.com/site/vasset/public/attachments/2024/06/18119/MorningSkate_060424.pdf) | opened_pdf |
| `1996-culture-nintendo` | [Nintendo — Our History](https://careers.nintendo.com/our-history/) | opened_page |
| `1996-culture-pokemon` | [The Pokémon Company — 30th anniversary](https://www.pokemon.co.jp/30th/) | opened_page |
| `1996-culture-tamagotchi` | [Bandai — Tamagotchi mobile release announcement, December 5, 2005](https://www.bandainamco.co.jp/en/releases/images/3/25977.pdf) | opened_pdf |
| `1996-culture-dolly` | [University of Edinburgh — Cloning of Dolly the Sheep, 1996](https://ourhistory.is.ed.ac.uk/index.php?title=Cloning_of_Dolly_the_Sheep%2C_1996) | opened_page |
| `1996-culture-pathfinder` | [NASA Science — Mars Pathfinder](https://science.nasa.gov/mission/mars-pathfinder/) | opened_page |
| `1996-culture-deep-blue` | [IBM — Deep Blue](https://www.ibm.com/history/deep-blue) | opened_page |
| `1996-culture-internet-archive` | [Internet Archive — October 24, 2001 press release, preserved by Library Technology Guides](https://librarytechnology.org/pr/9384/internet-archive-launches-wayback-machine-free-service-enables-users-to-access-archived-versions-of-web-sites-dating-from-1996) | opened_page |

## Integration and committee use

The backend lane confirmed that the existing staff trivia endpoint will import this file through an explicit committee action as a **draft game**. The matching fields are `prompt`, `choices`, zero-based `correct_index`, `explanation`, `points` and `sources`; the bank's key supports identifying the imported content. The importer and its permissions are owned and tested by the backend lane, not established by this research record.

Suggested operator sequence: import the draft bank, review wording and sources, choose the questions for the round, publish selected questions, then open the round when the host is ready. Close the round before reviewing and publishing results. The default instructions state one scored attempt per question, 100 points for a correct answer, zero for an incorrect answer, equal scores sharing the same place, and no speed bonus or promised prize. The actual server implementation must enforce these rules.

Research review does not mean that the committee has accepted every question or independently operated the feature. A moderator may correct or retire a draft question; edits after play begins require the application's documented integrity rules rather than silently changing scored answers.

## Local validation and handoff

The structural validation passed: 30 unique keys and prompts; all four choices nonempty and distinct; valid integer answer indices; every year exactly 1996; every score 100; every source HTTPS with title, access date, supported claim and verification method. Answer indices are distributed 8/8/7/7 across positions 0/1/2/3, in a deterministic shuffled order. The longest prompt is 93 characters and the longest explanation is 156, within the current staff endpoint's limits.

`node .famtastic/verify-repository.mjs` was run and **failed** with `common_directory_mismatch`: “The worktree common directory is not owned by this customer site.” This is recorded without changing repository ownership or unrelated work. It is not a question-bank validation failure and is not counted as a pass.

| Evidence axis | State |
| --- | --- |
| Research and authored source | `source_only`: 30 checked questions and source register |
| Bank structure | `local_tested`: validation passed |
| Database import and game scoring | Handed to backend/integration lane; not proved here |
| Hosted behavior | Not exercised by this lane |
| Provider or external mutation | `not_applicable` |
| Committee review and uncoached use | `pending` |
| Public scores or winners | None created by this lane |
| Graphic generation/spend | None / $0 |

<div align="center"><a href="https://famtasticdesigns.com/"><img src="https://mbsh96reunion.com/assets/famtastic/famtastic-designs-logo-v1.png" alt="FAMtastic Designs" width="160"></a></div>

