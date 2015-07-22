# SecurePrint
## Server

SecurePrint is a working prototype for an enterprise secure print system. This package
contains the source code for the API server.

For installation information, see: [SecurePrint-DevPackage](https://github.com/emisaacson/SecurePrint-DevPackage).

A secure print system is one that requires the user to be *phyically* at the printer in order
to print the document. This prevents confidential information be left on the printer inadvertently
and being seen or taken by unauthorized individuals.

This system solves this problem by placing a RESTful API in front of a CUPS print server and
provides a mobile app to each user. The users print to the CUPS server and the job is held
indefinitely by default. The user then physically approaches the print server with app in hand.
A low energy bluetooth beacon is attached to the printer and the app prompts the user to
release the print jobs when the user is within close proximity to the beacon. When the job
is released by the mobile app, the job is sent to the printer the user is standing near.

This approach is good for users because it's convenient - there's no need to choose
the printer to print to beforehand. The catchall printer is the only printer that needs to be
available, and then the user can just physically go to whatever printer is most convenient and
print to that one with the app. If the user's first choice is in use, they can just walk to another one without
having to wait or cancel the job or start a new job.

## License

GPL v.2