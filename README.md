# Persistent Identifiers

The Persistent Identifiers module allows users to create or import persistent identifiers (PIDs) and assign them to Omeka S items. These PIDs can be made ("minted") and assigned at item creation, via the item editing screen, or during batch edits. They can be removed individually or in batches. Existing PIDs may be extracted from item metadata. PIDs can also be minted and assigned in bulk during an item import process. 

Once assigned, accessing an item's PID in-browser resolves to a stable, non-site-specific landing page containing the item's metadata, media, and any sites the item is assigned to.

This module currently creates [Digital Object Identifiers (DOIs)](https://www.doi.org), commonly used for scholarly publications and resources, and [Archival Resource Keys (ARKs)](https://www.arks.org), for archival materials. 

To create these identifiers, Omeka users will require an account with an external service provider. 

The PID module is designed in a flexible way to connect with various PID service APIs for PID creation or import. The module can currently connect to the following PID APIs (i.e. all other PID services will require additional code):

- [ARKs](https://arks.org) locally, or via [EZID](https://ezid.cdlib.org) (EZID is not currently accepting new users)
- [DOIs](https://www.doi.org) via [DataCite](https://datacite.org)

See the [Omeka S user manual](http://omeka.org/s/docs/user-manual/modules/persistentidentifiers/) for user documentation.

# Copyright

PersistentIdentifiers is Copyright © 2019-present Corporation for Digital Scholarship, Vienna, Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code
under the GNU General Public License, version 3 (GPLv3). The full text
of this license is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
