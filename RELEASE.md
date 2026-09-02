# VIEW! Privacy release contract

Gebruik uitsluitend een GitHub Release met een lowercase tag in de vorm `vX.Y.Z`.
De release bevat precies één verplichte asset: `view-privacy-X.Y.Z.zip`. GitHub's
automatische **Source code (zip)** is geen pluginpakket en mag nooit als update-
asset worden gebruikt.

Het ZIP-bestand bevat exact één rootmap:

```
view-privacy/
  view-privacy.php
```

De versie in de pluginheader, `VIEW_PRIVACY_VERSION`, `readme.txt` en de assetnaam
moet identiek zijn. Bouw lokaal met:

```powershell
./scripts/build-release-package.ps1 -Version X.Y.Z -OutputDirectory ./dist
```

De build valideert de rootmap, hoofdfile, header en assetnaam. Publiceer pas na een
geslaagde CI-run de asset, daarna de GitHub Release en als laatste de tag `vX.Y.Z`.
De reeds bestaande tag `V4.0.0` blijft alleen voor updatecompatibiliteit leesbaar;
nieuwe uppercase tags mogen niet worden gemaakt.
