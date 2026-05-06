# MediaGallery

MediaGallery is a repository plugin to store media items like pictures, videos and audios to view and share it in ILIAS as a gallery.

**Minimum ILIAS Version:**
10.0

**Maximum ILIAS Version:**
10.999

**Supported Languages:**
German, English

### Requirements
- ImageMagick
```shell
sudo apt-get install imagemagick imagemagick-doc
```

### Quick Installation Guide
Navigate to the ILIAS root directory and clone the repository:
```shell
mkdir -p public/Customizing/global/plugins/Services/Repository/RepositoryObject
```
```shell
cd public/Customizing/global/plugins/Services/Repository/RepositoryObject
```
```shell
git clone -b release_9 https://github.com/leifos-gmbh/MediaGallery.git MediaGallery
```
Finally enable the plugin in the ILIAS administration.
