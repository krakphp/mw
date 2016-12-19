===============
Troubleshooting
===============

Here are few common errors and how to resolve them

"No middleware returned a response" Error
=========================================

When you get this error or something similar, this means that no middleware in the set of middleware returned a response.

You can get this error if you:

- Forget to put a return statement in your middleware so the chain breaks and no response is returned.
- Have a logic error where no middleware actually accepts the response

If you are having trouble finding which handler is causing the issue, you can use a MwStack instead. These provide better error messages because each middleware stack has a name which can help track down which middlware stack is causing the problem.
