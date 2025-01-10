<?php

header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Embedder-Policy: require-corp');
header('Content-Type: text/html');

?>


<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Test</title>
    <meta name="viewport" content="width=device-width">
</head>

<body>




<script id="worker" type="javascript/worker">

const globalThis = {}


  self.addEventListener('message', (event) =>
  {
      const i32a = new Int32Array( event.data )

    function sleep(ms) {
      return new Promise(resolve => setTimeout(resolve, ms));
    }

    let stream = new ReadableStream(
        {
            async * [ Symbol.asyncIterator ]()
            {
                while ( true )
                    yield ( await stream.read() ).value
            },
            async pull( controller )
            {
                for await ( const chunk of this )
                {   

                    Atomics.store(i32a, 1, chunk *2 );
                    Atomics.notify(i32a, 1, 1)  

                    //console.log( chunk )
                    this.start( controller, chunk )
                }
            },
            async start( controller, value = 0 )
            {
                Promise.resolve( Atomics.waitAsync( i32a, 0, value ).value )
                .then( async value =>
                {
                    //await sleep(500)
                    controller.enqueue( Atomics.load( i32a, 0 ) )
                })
            }
        },
        new CountQueuingStrategy({ highWaterMark: 1 })
    ).getReader()



  }, false);

</script>


<script type="module">




const ro = ( observer =>
{
    const blob = new Blob([document.querySelector('#worker').textContent], { type: "text/javascript" })
    const worker = new Worker(URL.createObjectURL(blob));

    const sab = new SharedArrayBuffer( 2 * Int32Array.BYTES_PER_ELEMENT )
    worker.postMessage( sab )

    const i32a = new Int32Array( sab )
    Atomics.store( i32a.fill( 0, 0, 1 ) )

    addEventListener( 'mousemove', event =>
    {
        Atomics.store ( i32a, 0, event.clientX )
        Atomics.notify( i32a, 0, 1 )
    }, false)

    let stream = new ReadableStream(
    {
        async * [ Symbol.asyncIterator ]()
        {
            while ( true )
                yield ( await stream.read() ).value
        },
        async pull( controller )
        {
            for await ( const chunk of this )
            {
                console.log('Doubled Mouse X ==> ', chunk)
                this.start( controller, chunk )
            }
        },
        async start( controller, value = 0 )
        {
            Promise.resolve( Atomics.waitAsync( i32a, 1, value ).value )
            .then( value =>
            {
                controller.enqueue( Atomics.load( i32a, 1 ) )
            })
        }
    },
        new CountQueuingStrategy({ highWaterMark: 1 })
    ).getReader()
})()





</script>

</body>

</html>