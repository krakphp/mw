===============
Troubleshooting
===============

Here are few common errors and how to resolve them

"Cannot invoke last middleware in chain. No middleware returned a result." NoResultException
============================================================================================

When you get this error or something similar, this means that no middleware in the set of middleware returned a response.

You can get this error if you:

- Forget to put a return statement in your middleware so the chain breaks and no response is returned.
- Have a logic error where no middleware actually accepts the response

If you are having trouble finding which handler is causing the issue, you can use add a `guard` middleware when you compose your middleware set to provide custom error messages.

"Middleware cannot be invoked because it does not contain the '' method"
========================================================================

This exception is thrown when using the ``methodInvoke`` for composing your middleware. This means that one of the middleware on your stack doesn't have the proper method to be called.

To fix this, you should check your middleware stack and verify that every middleware has the proper method. The stack trace should also show you which class instance caused the problem to help you track down the problem.
