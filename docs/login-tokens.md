In order to create an account, you will need to `exec` into an api container and create a login token with the `artisan` command.

To create the invite code `invite1`, use the following command:
```
php artisan wbs-invitation:create invite1
```
You should see the output `Successfully created invitation: invite1`

You can view all invitation codes that have been created (and not used) with: 
```
php artisan wbs-invitation:all
```

And delete (unused) invitation codes with:

```
php artisan wbs-invitation:delete <code>
```
