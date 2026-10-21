# ECharts runtime for the standalone ITA MOCK dashboard

Pinned version: 6.1.0. Downloaded 2026-10-21 from the apache/echarts GitHub tag, not a runtime CDN. The official Apache download index listed6.1.0 at inspection: https://downloads.apache.org/echarts/ . Installation guidance: https://echarts.apache.org/handbook/en/basics/download/ .

SOURCE.json records exact HTTPS URLs, local SHA256 and byte counts for echarts.min.js, LICENSE and NOTICE. Those three downloaded files were read back and hashes checked. These are locally computed checksums, not a claim that Apache OpenPGP signatures or an independently published checksum were verified. Preserve LICENSE and NOTICE with the runtime; this directory does not contain private config or a Node dependency installation.

Scoped .gitattributes entries disable line-ending conversion for the three downloaded payload files so their SHA256 stays unchanged after Git checkout. SOURCE.json and this README remain normal text files.

The minified distribution is consumed as a browser script only by assets/ita/mock/dashboard.html. No production layout or PHP bootstrap includes it in this step. No Node build on production. Reproduce these exact files from the URLs in SOURCE.json, reject a hash mismatch and do not silently replace with latest. UI library use is permitted by AGENTS.md; this does not approve backend gate10.
