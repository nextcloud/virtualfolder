# virtualfolder

[![PHPUnit](https://github.com/icewind1991/virtualfolder/actions/workflows/phpunit.yml/badge.svg)](https://github.com/icewind1991/virtualfolder/actions)

Example app for creating virtual folders

⚠  Requires https://github.com/nextcloud/server/pull/29886 ⚠ 

## Using the app "as is"

The app in its base form allows the admin to setup virtual folders using occ commands.

Once a folder is configured it will show up at the configured mount point for the target user as a folder
containing the provided files. The virtual folder itself will be read only while the configured files contained
inside will have the full permissions from the source user.

#### Create a new virtual folder

```bash
occ virtualfolder:create <target user> <name> [<file ids>...]
```

#### List created virtual folders

```bash
occ virtualfolder:list
```

#### Delete a virtual folder

```bash
occ virtualfolder:delete <folder id>
```

#### Move a virtual folder

```bash
occ virtualfolder:move <folder id> <new mount point>
```

Note that the provided mountpoint is absolute.

## Using the app as a base for your own app

The app is setup to be easily adaptable into apps that create virtual folders for specific use cases.

### Automatically setting up folders

Custom logic for configuring virtual folders can be setup in `lib/Folder/FolderConfigManager.php`
by adding your own logic to the `getFoldersForUser` method.
All other methods in that class are only used by unit tests and the management command and can thus be removed
if you remove the management commands.

### Limiting the permissions of files inside the virtual folder

Permissions of files in the virtual folder can be restricted by changing `getStorageForSourceFile`
in `lib/Mount/VirtualFolderMountProvider` to wrap the created `Jail` storage wrapper into a `PermissionsMask` wrapper
to apply a mask to the permissions for the files in the virtual folder.


## DAV api

virtual folders are exposed trough webdav under the `virtualfolder` endpoint.

### Creating a folder

```bash
curl -u user:password -X MKCOL 'https://cloud.example.com/dav/virtualfolder/user/my_folder_name
```

Note: virtual folders created trough webdav will within the users hidden directory and thus will not be visible in the normal files UI.
It can still be accessed through the normal filesystem and dav apis at `/.hidden_<instance_id>/my_folder_name/`.  

### Listing available folders

```bash
curl -u user:password -X PROPFIND 'https://cloud.example.com/dav/virtualfolder/user
```

### Adding a file to a folder

```bash
curl -X COPY -u user:password -H 'Destination: https://cloud.example.com/dav/virtualfolder/user/my_folder_name/file.txt' 'https://cloud.example.com/remote.php/dav/files/user/file.txt'
```

### Listing the contents of a folder

```bash
curl -u user:password -X PROPFIND 'https://cloud.example.com/dav/virtualfolder/user/my_folder_name
```

When listing a folder you can request the `{http://nextcloud.org/ns}canonical-path` property to get the path to the file in the hidden user folder, this path can be used through the normal dav apis.

### Removing a file from a folder

```bash
curl -u user:password -X DELETE 'https://cloud.example.com/dav/virtualfolder/user/my_folder_name/file.txt
```

## Code overview

- `AppInfo`
  - `Application`: setup the app by registering the mount provided
- `Command/*`: commands to allow the admin to configure virtual mounts
- `Folder`
  - `FolderConfig`: data class to store information for a configured folder
  - `FolderConfigManager`: manage configured virtual folders
  - `SourceFile`: holds the data of each source file in the virtual folder and allows access to the source storage
  - `VirtualFolder`: data class for virtual folders and source file information
  - `VirtualFolderFactory`: get folder and source file info from folder configuration
- `Migration`: database migration script for storing admin configured virtual folders
- `Mount`
  - `VirtualFolderMount`: `MountPoint` subclass to allow setting a custom folder icon
  - `VirtualFolderMountProvider`: mount provider that setups up all mounts required for the virtual folder  
	this includes one mount for the root of the virtual folder and one mount for every file inside it
- `Storage`
  - `EmptyStorage`: an empty readonly storage for use as the virtual folder root
  - `LazyWrapper`: a storage wrapper that allows delaying setup of the source storage until the storage is used 
  - `LazeCacheWrapper`: same as `LazyWrapper` but then for the source cache
