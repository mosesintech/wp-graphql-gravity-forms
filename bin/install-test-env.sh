#!/usr/bin/env bash

if [[ ! -f ".env" ]]; then
  echo "No .env file was detected. .env.dist has been copied to .env"
  echo "Open the .env file and enter values to match your local environment"
  cp .env.dist .env
fi

source .env

print_usage_instruction() {
  echo "ERROR!"
  echo "Values in the .env file are missing or incorrect."
  echo "Open the .env file at the root of this plugin and enter values to match your local environment settings"
	exit 1
}

if [[ -z "$TEST_DB_NAME" ]]; then
	echo "TEST_DB_NAME not found"
	print_usage_instruction
else
	DB_NAME=$TEST_DB_NAME
fi
if [[ -z "$TEST_DB_USER" ]]; then
	echo "TEST_DB_USER not found"
	print_usage_instruction
else
	DB_USER=$TEST_DB_USER
fi

DB_HOST=${TEST_DB_HOST-localhost}
DB_PASS=${TEST_DB_PASSWORD-""}
WP_VERSION=${WP_VERSION-latest}
TMPDIR=${TMPDIR-/tmp}
TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")
WP_TESTS_DIR=${WP_TESTS_DIR-$TMPDIR/wordpress-tests-lib}
WP_CORE_DIR=${WP_ROOT_FOLDER-$TMPDIR/wordpress/}
PLUGIN_DIR=$(pwd)
DB_SERVE_NAME=${DB_SERVE_NAME-wpgatsby_serve}
SKIP_DB_CREATE=${SKIP_DB_CREATE-false}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\-(beta|RC)[0-9]+$ ]]; then
	WP_BRANCH=${WP_VERSION%\-*}
	WP_TESTS_TAG="branches/$WP_BRANCH"

elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
	WP_TESTS_TAG="branches/$WP_VERSION"
elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0-9]+ ]]; then
	if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
		# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
		WP_TESTS_TAG="tags/${WP_VERSION%??}"
	else
		WP_TESTS_TAG="tags/$WP_VERSION"
	fi
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
	WP_TESTS_TAG="trunk"
else
	# http serves a single offer, whereas https serves multiple. we only want one
	download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
	grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
	if [[ -z "$LATEST_VERSION" ]]; then
		echo "Latest WordPress version could not be found"
		exit 1
	fi
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi
set -ex

install_wp() {

	if [ -d $WP_CORE_DIR ]; then
		return;
	fi

	mkdir -p $WP_CORE_DIR

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p $TMPDIR/wordpress-nightly
		download https://wordpress.org/nightly-builds/wordpress-latest.zip  $TMPDIR/wordpress-nightly/wordpress-nightly.zip
		unzip -q $TMPDIR/wordpress-nightly/wordpress-nightly.zip -d $TMPDIR/wordpress-nightly/
		mv $TMPDIR/wordpress-nightly/wordpress/* $WP_CORE_DIR
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		elif [[ $WP_VERSION =~ [0-9]+\.[0-9]+ ]]; then
			# https serves multiple offers, whereas http serves single.
			download https://api.wordpress.org/core/version-check/1.7/ $TMPDIR/wp-latest.json
			if [[ $WP_VERSION =~ [0-9]+\.[0-9]+\.[0] ]]; then
				# version x.x.0 means the first release of the major version, so strip off the .0 and download version x.x
				LATEST_VERSION=${WP_VERSION%??}
			else
				# otherwise, scan the releases and get the most up to date minor version of the major release
				local VERSION_ESCAPED=`echo $WP_VERSION | sed 's/\./\\\\./g'`
				LATEST_VERSION=$(grep -o '"version":"'$VERSION_ESCAPED'[^"]*' $TMPDIR/wp-latest.json | sed 's/"version":"//' | head -1)
			fi
			if [[ -z "$LATEST_VERSION" ]]; then
				local ARCHIVE_NAME="wordpress-$WP_VERSION"
			else
				local ARCHIVE_NAME="wordpress-$LATEST_VERSION"
			fi
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  $TMPDIR/wordpress.tar.gz
		tar --strip-components=1 -zxmf $TMPDIR/wordpress.tar.gz -C $WP_CORE_DIR
	fi

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
}

install_db() {

	if [ ${SKIP_DB_CREATE} = "true" ]; then
		return 0
	fi

	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	RESULT=`mysql -u $DB_USER --password="$DB_PASS" --skip-column-names -e "SHOW DATABASES LIKE '$DB_NAME'"$EXTRA`
	if [ "$RESULT" != $DB_NAME ]; then
			mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
	fi
}

configure_wordpress() {
    cd $WP_CORE_DIR
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST" --skip-check --force=true
    wp core install --url=$WP_DOMAIN --title=GFTests --admin_user=$ADMIN_USERNAME --admin_password=$ADMIN_PASSWORD --admin_email=$ADMIN_EMAIL
    wp rewrite structure '/%year%/%monthnum%/%postname%/'
}

install_gravityforms() {
	if [ ! -d $WP_CORE_DIR/wp-content/plugins/gravityforms ]; then
		echo "Cloning Gravity Forms"
		if [ -n "$GIT_USER" ] && [ -n "$GIT_TOKEN" ] && [ -n "$GF_REPO" ]; then
		git clone https://$GIT_USER:$GIT_TOKEN@$GF_REPO $WP_CORE_DIR/wp-content/plugins/gravityforms
		else
			git clone https://github.com/wp-premium/gravityforms.git $WP_CORE_DIR/wp-content/plugins/gravityforms
		fi
	fi
	echo "Cloning Gravity Forms"
	wp plugin activate gravityforms
}

install_gravityforms_signature() {
	if [ ! -d $WP_CORE_DIR/wp-content/plugins/gravityformssignature ]; then
		echo "Cloning Gravity Forms Signature"
			if [ -n "$GIT_USER" ] && [ -n "$GIT_TOKEN" ] && [ -n "$GF_SIGNATURE_REPO" ]; then
		git clone https://$GIT_USER:$GIT_TOKEN@$GF_SIGNATURE_REPO $WP_CORE_DIR/wp-content/plugins/gravityformssignature
		else
			git clone https://github.com/wp-premium/gravityformssignature.git $WP_CORE_DIR/wp-content/plugins/gravityformssignature
		fi
	fi
	wp plugin activate gravityformssignature
}

install_gravityforms_chainedselects() {
	if [ ! -d $WP_CORE_DIR/wp-content/plugins/gravityformschainedselects ]; then
		echo "Cloning Gravity Forms Signature"
			if [ -n "$GIT_USER" ] && [ -n "$GIT_TOKEN" ] && [ -n "$GF_CHAINEDSELECTS_REPO" ]; then
		git clone https://$GIT_USER:$GIT_TOKEN@$GF_CHAINEDSELECTS_REPO $WP_CORE_DIR/wp-content/plugins/gravityformschainedselects
		else
			echo "To test Chained Selects, please manually install the plugin in your dev environment."
		fi
	fi
	wp plugin activate gravityformschainedselects
}

setup_plugin() {

	# Add this repo as a plugin to the repo
	if [ ! -d $WP_CORE_DIR/wp-content/plugins/wp-graphql-gravity-forms ]; then
		ln -s $PLUGIN_DIR $WP_CORE_DIR/wp-content/plugins/wp-graphql-gravity-forms
		cd $WP_CORE_DIR/wp-content/plugins
		pwd
		ls
	fi

	cd $PLUGIN_DIR

	composer install

	cd $WP_CORE_DIR

  wp plugin list

  # Install WPGraphQL
  wp plugin install wp-graphql

	# Activate WPGraphQL
	wp plugin activate wp-graphql

	# activate the plugin
	wp plugin activate wp-graphql-gravity-forms

	# Flush the permalinks
	wp rewrite flush

	# Export the db for codeception to use
	wp db export $PLUGIN_DIR/tests/_data/dump.sql

}

install_wp
install_db
configure_wordpress
install_gravityforms
install_gravityforms_signature
install_gravityforms_chainedselects
setup_plugin