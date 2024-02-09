# MediaGallery

MediaGallery is a repository plugin to store media items like pictures, videos and audios to view and share it in ILIAS as a gallery.

**Minimum ILIAS Version:**
8.0

**Maximum ILIAS Version:**
8.999

**Supported Languages:**
German, English

### Requirements
- ImageMagick
```shell
sudo apt-get install imagemagick imagemagick-doc
```

### Quick Installation Guide
1. Download the Plugin:
```shell
cd <ILIAS_ROOT>
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
cd Customizing/global/plugins/Services/Repository/RepositoryObject
git clone -b release_8 https://github.com/leifos-gmbh/MediaGallery.git MediaGallery
```
2. Navigate to the plugin-menu on the ILIAS web interface.
3. Look for the MediaGallery plugin in the table and ... 
   1. hit the "Action" button and select "Install".
   2. hit the "Action" button and select "Activate".
   3. hit the "Action" button and select "Refresh Languages".
