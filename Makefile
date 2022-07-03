# https://docs.microsoft.com/en-us/visualstudio/ide/customize-build-and-debug-tasks-in-visual-studio?view=vs-2022#define-custom-build-tasks
# https://ftp.gnu.org/old-gnu/Manuals/make-3.79.1/html_chapter/make_6.html

# Define build variables
ssh_host = bpr-www
dest_dir = ~/dev-service-gateway # this is a symlink to the actual directory on the server

# Standard build task
build: deploy

# Deploy task to send the contents of the directory to the remote server recursively
deploy:
	rsync -rlvuzh -e ssh --delete \
		--exclude "storage/logs/" \
		--exclude "node_modules/" \
		--exclude "vendor/" \
		--exclude=".svn/" \
		--exclude=".git/" \
		--exclude=".vscode/" \
	. ${ssh_host}:${dest_dir};
